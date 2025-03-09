<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;
use Spatie\PdfToImage\Pdf;
use FFMpeg\FFMpeg;

class FilePreviewService
{
    protected $previewPath = 'previews';
    protected $maxPreviewSize = 300; // pixels

    public function generatePreview(UploadedFile $file): ?string
    {
        try {
            $mimeType = $file->getMimeType();
            $extension = $file->getClientOriginalExtension();

            // Generate a unique preview filename
            $previewFileName = sprintf(
                '%s-%s.%s',
                Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)),
                Str::random(10),
                'webp' // We'll convert all image previews to webp for optimization
            );

            $previewPath = "{$this->previewPath}/{$previewFileName}";

            // Handle different file types
            if (Str::startsWith($mimeType, 'image/')) {
                return $this->handleImage($file, $previewPath);
            }

            if ($mimeType === 'application/pdf') {
                return $this->handlePdf($file, $previewPath);
            }

            if (Str::startsWith($mimeType, 'video/')) {
                return $this->handleVideo($file, $previewPath);
            }

            // For other file types, return a type-based placeholder
            return $this->getPlaceholderForType($extension);

        } catch (\Exception $e) {
            \Log::error('Preview generation failed', [
                'file' => $file->getClientOriginalName(),
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    protected function handleImage(UploadedFile $file, string $previewPath): string
    {
        $image = Image::make($file)
            ->orientate() // Fix image orientation
            ->resize($this->maxPreviewSize, $this->maxPreviewSize, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            })
            ->encode('webp', 80); // Convert to WebP with 80% quality

        Storage::disk('public')->put($previewPath, $image);

        return Storage::disk('public')->url($previewPath);
    }

    protected function handlePdf(UploadedFile $file, string $previewPath): string
    {
        $tempPath = storage_path('app/temp/' . Str::random(40) . '.pdf');
        $file->move(dirname($tempPath), basename($tempPath));

        try {
            $pdf = new Pdf($tempPath);
            $previewImage = $pdf->setPage(1)
                ->setOutputFormat('webp')
                ->setResolution(150)
                ->saveImage(storage_path('app/public/' . $previewPath));

            return Storage::disk('public')->url($previewPath);
        } finally {
            @unlink($tempPath); // Clean up temp file
        }
    }

    protected function handleVideo(UploadedFile $file, string $previewPath): string
    {
        $tempPath = storage_path('app/temp/' . Str::random(40) . '.' . $file->getClientOriginalExtension());
        $file->move(dirname($tempPath), basename($tempPath));

        try {
            $ffmpeg = FFMpeg::create([
                'ffmpeg.binaries' => config('media.ffmpeg_path'),
                'ffprobe.binaries' => config('media.ffprobe_path'),
            ]);

            $video = $ffmpeg->open($tempPath);
            $frame = $video->frame(\FFMpeg\Coordinate\TimeCode::fromSeconds(1));
            $frame->save(storage_path('app/public/' . $previewPath));

            // Optimize the preview
            $image = Image::make(storage_path('app/public/' . $previewPath))
                ->resize($this->maxPreviewSize, $this->maxPreviewSize, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                })
                ->encode('webp', 80);

            Storage::disk('public')->put($previewPath, $image);

            return Storage::disk('public')->url($previewPath);
        } finally {
            @unlink($tempPath); // Clean up temp file
        }
    }

    protected function getPlaceholderForType(string $extension): string
    {
        $placeholders = [
            'doc' => 'word',
            'docx' => 'word',
            'xls' => 'excel',
            'xlsx' => 'excel',
            'ppt' => 'powerpoint',
            'pptx' => 'powerpoint',
            'zip' => 'archive',
            'rar' => 'archive',
            '7z' => 'archive',
            'txt' => 'text',
        ];

        $type = $placeholders[$extension] ?? 'generic';
        return asset("assets/placeholders/file-{$type}.svg");
    }
}
