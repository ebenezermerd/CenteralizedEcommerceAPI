<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateMailRequest;
use App\Http\Resources\MailResource;
use App\Http\Resources\MailLabelResource;
use App\Models\Mail;
use App\Models\MailLabel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Services\FilePreviewService;
use App\Models\User;

class MailController extends Controller
{
    protected $filePreviewService;

    public function __construct(FilePreviewService $filePreviewService)
    {
        $this->filePreviewService = $filePreviewService;
        Log::info('MailController initialized', ['service' => get_class($filePreviewService)]);
    }

    public function list(Request $request)
    {
        $labelId = $request->input('labelId');
        $userId = auth()->id();
        $perPage = 20;

        $query = Mail::with(['from', 'to', 'labels', 'attachments'])
            ->where(function ($q) use ($userId) {
                $q->where('from_user_id', $userId)
                    ->orWhereHas('to', function ($query) use ($userId) {
                        $query->where('users.id', $userId);
                    });
            });

        // Handle system folders and custom labels
        if ($labelId) {
            if (is_numeric($labelId)) {
                // Custom label
                $query->whereHas('labels', function ($q) use ($labelId) {
                    $q->where('mail_labels.id', $labelId);
                });
            } else {
                // System folder
                switch ($labelId) {
                    case 'inbox':
                        $query->whereHas('to', function ($q) use ($userId) {
                            $q->where('users.id', $userId);
                        })->whereHas('labels', function ($q) {
                            $q->where('name', 'inbox');
                        });
                        break;
                    case 'sent':
                        $query->where('from_user_id', $userId)
                            ->whereHas('labels', function ($q) {
                                $q->where('name', 'sent');
                            });
                        break;
                    case 'draft':
                        $query->whereHas('labels', function ($q) {
                            $q->where('name', 'draft');
                        });
                        break;
                    case 'trash':
                        $query->whereHas('labels', function ($q) {
                            $q->where('name', 'trash');
                        });
                        break;
                    case 'spam':
                        $query->whereHas('labels', function ($q) {
                            $q->where('name', 'spam');
                        });
                        break;
                }
            }
        }

        $mails = $query->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'mails' => MailResource::collection($mails),
            'pagination' => [
                'total' => $mails->total(),
                'per_page' => $mails->perPage(),
                'current_page' => $mails->currentPage(),
                'last_page' => $mails->lastPage()
            ]
        ]);
    }

    public function details(Request $request)
    {
        Log::info('Fetching mail details', ['mailId' => $request->input('mailId')]);

        $mail = Mail::with(['from', 'to', 'labels', 'attachments'])
            ->findOrFail($request->input('mailId'));

        Log::info('Mail details fetched', [
            'mailId' => $mail->id,
            'subject' => $mail->subject,
            'from' => $mail->from->email ?? null
        ]);

        return response()->json([
            'mail' => new MailResource($mail),
        ]);
    }

    public function labels()
    {
        Log::info('Fetching all mail labels');
        $labels = MailLabel::all();
        Log::info('Labels fetched', ['count' => $labels->count()]);

        return response()->json([
            'labels' => MailLabelResource::collection($labels),
        ]);
    }

    public function create(CreateMailRequest $request)
    {
        try {
            DB::beginTransaction();

            $validated = $request->validated();
            $sender_id = auth()->id();

            // Create the mail for sender (in sent folder)
            Log::info('mail creation', [
                'from_user_id' => auth()->user()->id, // Get authenticated user's ID as foreign key
                'subject' => $validated['subject'],
                'message' => $validated['message'],
                'folder' => 'sent',
                'is_important' => false,
                'is_starred' => false,
                'is_unread' => false,
            ]);
            $mail = Mail::create([
                'from_user_id' => auth()->user()->id, // Get authenticated user's ID as foreign key

                'subject' => $validated['subject'],
                'message' => $validated['message'],
                'folder' => 'sent',
                'is_important' => false,
                'is_starred' => false,
                'is_unread' => false, // Not unread for sender
            ]);
            Log::info('Mail created', ['mail' => $mail]);


            // Get recipient IDs
            $recipientIds = User::whereIn('email', $request->input('to'))
                ->pluck('id')
                ->toArray();

            if (empty($recipientIds)) {
                throw new \Exception('No valid recipients found');
            }

            // Sync recipients
            $mail->to()->sync($recipientIds);

            // Add system labels
            $sentLabel = MailLabel::where('name', 'sent')->first();
            $inboxLabel = MailLabel::where('name', 'inbox')->first();

            // Assign 'sent' label for sender's copy
            $mail->labels()->attach($sentLabel->id);

            // Create copies for recipients (in inbox folder)
            foreach ($recipientIds as $recipientId) {
                $recipientCopy = Mail::create([
                    'from_user_id' => auth()->user()->id, // Get authenticated user's ID as foreign key

                    'subject' => $validated['subject'],
                    'message' => $validated['message'],
                    'folder' => 'inbox',
                    'is_important' => false,
                    'is_starred' => false,
                    'is_unread' => true, // Unread for recipients
                ]);

                // Add recipient
                $recipientCopy->to()->attach($recipientId);

                // Add inbox label for recipient's copy
                $recipientCopy->labels()->attach($inboxLabel->id);

                // If there are custom labels, add them
                if (!empty($validated['labelIds'])) {
                    $recipientCopy->labels()->attach($validated['labelIds']);
                }

                // Copy attachments if any
                if ($request->hasFile('attachments')) {
                    foreach ($request->file('attachments') as $file) {
                        $path = $file->store('mail-attachments');
                        $recipientCopy->attachments()->create([
                            'name' => $file->getClientOriginalName(),
                            'size' => $file->getSize(),
                            'type' => $file->getMimeType(),
                            'path' => $path,
                            'preview' => $this->generatePreview($file),
                        ]);
                    }
                }
            }

            DB::commit();
            return new MailResource($mail->load(['from', 'to', 'labels', 'attachments']));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Mail creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Failed to create mail',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function saveDraft(CreateMailRequest $request)
    {
        try {
            DB::beginTransaction();

            $validated = $request->validated();

            $mail = Mail::create([
                'from_user_id' => auth()->user()->id,
                'subject' => $validated['subject'] ?? '(No subject)',
                'message' => $validated['message'] ?? '',
                'folder' => 'draft',
                'is_unread' => false,
            ]);

            // Add recipients if provided
            if (!empty($validated['to'])) {
                $recipientIds = User::whereIn('email', $validated['to'])
                    ->pluck('id')
                    ->toArray();
                $mail->to()->sync($recipientIds);
            }

            // Add draft label
            $draftLabelId = MailLabel::where('name', 'draft')->first()->id;
            $mail->labels()->sync([$draftLabelId]);

            // Handle attachments
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('mail-attachments');
                    $mail->attachments()->create([
                        'name' => $file->getClientOriginalName(),
                        'size' => $file->getSize(),
                        'type' => $file->getMimeType(),
                        'path' => $path,
                        'preview' => $this->generatePreview($file),
                    ]);
                }
            }

            DB::commit();
            return new MailResource($mail->load(['from', 'to', 'labels', 'attachments']));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to save draft', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to save draft'], 500);
        }
    }

    public function updateDraft(CreateMailRequest $request, string $id)
    {
        try {
            DB::beginTransaction();

            $mail = Mail::findOrFail($id);
            $validated = $request->validated();

            // Update basic info
            $mail->update([
                'subject' => $validated['subject'] ?? '(No subject)',
                'message' => $validated['message'] ?? '',
            ]);

            // Update recipients if provided
            if (isset($validated['to'])) {
                $recipientIds = User::whereIn('email', $validated['to'])
                    ->pluck('id')
                    ->toArray();
                $mail->to()->sync($recipientIds);
            }

            // Update attachments
            if ($request->hasFile('attachments')) {
                // Delete existing attachments
                foreach ($mail->attachments as $attachment) {
                    Storage::delete($attachment->path);
                }
                $mail->attachments()->delete();

                // Add new attachments
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('mail-attachments');
                    $mail->attachments()->create([
                        'name' => $file->getClientOriginalName(),
                        'size' => $file->getSize(),
                        'type' => $file->getMimeType(),
                        'path' => $path,
                        'preview' => $this->generatePreview($file),
                    ]);
                }
            }

            DB::commit();
            return new MailResource($mail->load(['from', 'to', 'labels', 'attachments']));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update draft', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to update draft'], 500);
        }
    }

    public function markAsRead(string $mailId)
    {
        Log::info('Marking mail as read', ['mailId' => $mailId]);
        $mail = Mail::findOrFail($mailId);
        $mail->update(['is_unread' => false]);
        Log::debug('Mail marked as read', ['mailId' => $mailId]);
        return response()->noContent();
    }

    public function toggleStar(string $mailId)
    {
        Log::info('Toggling mail star status', ['mailId' => $mailId]);
        $mail = Mail::findOrFail($mailId);
        $mail->update(['is_starred' => !$mail->is_starred]);
        Log::debug('Mail star status toggled', ['mailId' => $mailId, 'starred' => !$mail->is_starred]);
        return response()->noContent();
    }

    public function delete(string $mailId)
    {
        Log::info('Deleting mail', ['mailId' => $mailId]);
        $mail = Mail::findOrFail($mailId);

        // Delete attachments from storage
        foreach ($mail->attachments as $attachment) {
            Storage::delete($attachment->path);
            Log::debug('Attachment deleted', [
                'mailId' => $mailId,
                'attachmentId' => $attachment->id,
                'path' => $attachment->path
            ]);
        }

        $mail->delete();
        Log::info('Mail deleted successfully', ['mailId' => $mailId]);
        return response()->noContent();
    }

    public function search(Request $request)
    {
        Log::info('Searching mails', [
            'query' => $request->input('q'),
            'labelId' => $request->input('labelId')
        ]);

        $query = $request->input('q');
        $labelId = $request->input('labelId');
        $userId = auth()->id();

        // Start with base query including all necessary relationships
        $mailsQuery = Mail::with(['from', 'to', 'labels', 'attachments'])
            ->select('mails.*') // Explicit select to avoid ambiguous column names
            ->where(function ($q) use ($userId) {
                $q->where('from_user_id', $userId)
                    ->orWhereHas('to', function ($query) use ($userId) {
                        $query->where('users.id', $userId);
                    });
            });

        // Apply search conditions based on mail table fields
        if ($query) {
            $mailsQuery->where(function ($q) use ($query) {
                // Search in mail fields
                $q->where('subject', 'LIKE', "%{$query}%")
                    ->orWhere('message', 'LIKE', "%{$query}%")
                    ->orWhere('folder', 'LIKE', "%{$query}%")
                    // Search in sender information
                    ->orWhereHas('from', function ($subQ) use ($query) {
                        $subQ->where(function ($nameQ) use ($query) {
                            // Search in combined full name
                            $nameQ->whereRaw("CONCAT(firstName, ' ', lastName) LIKE ?", ["%{$query}%"])
                                // Also search individual fields for better matching
                                ->orWhere('firstName', 'LIKE', "%{$query}%")
                                ->orWhere('lastName', 'LIKE', "%{$query}%");
                        })
                            ->orWhere('email', 'LIKE', "%{$query}%");
                    })
                    // Search in recipients information
                    ->orWhereHas('to', function ($subQ) use ($query) {
                        $subQ->where(function ($nameQ) use ($query) {
                            // Search in combined full name
                            $nameQ->whereRaw("CONCAT(firstName, ' ', lastName) LIKE ?", ["%{$query}%"])
                                // Also search individual fields for better matching
                                ->orWhere('firstName', 'LIKE', "%{$query}%")
                                ->orWhere('lastName', 'LIKE', "%{$query}%");
                        })
                            ->orWhere('email', 'LIKE', "%{$query}%");
                    })
                    // Search in labels
                    ->orWhereHas('labels', function ($subQ) use ($query) {
                        $subQ->where('name', 'LIKE', "%{$query}%");
                    });
            });
        }

        // Apply label/folder filtering
        if ($labelId) {
            if (is_numeric($labelId)) {
                // Filter by custom label
                $mailsQuery->whereHas('labels', function ($q) use ($labelId) {
                    $q->where('mail_labels.id', $labelId);
                });
            } else {
                // Filter by system folder
                switch ($labelId) {
                    case 'sent':
                        $mailsQuery->where('from_user_id', $userId);
                        break;
                    case 'inbox':
                        $mailsQuery->whereHas('to', function ($q) use ($userId) {
                            $q->where('users.id', $userId);
                        });
                        break;
                    case 'starred':
                        $mailsQuery->where('is_starred', true);
                        break;
                    case 'important':
                        $mailsQuery->where('is_important', true);
                        break;
                    default:
                        $mailsQuery->where('folder', $labelId);
                }
            }
        }

        // Order by most recent first
        $mails = $mailsQuery->orderBy('created_at', 'desc')
            ->paginate(20); // Add pagination for better performance

        Log::info('Search completed', [
            'query' => $query,
            'results_count' => $mails->total(),
            'page' => $mails->currentPage()
        ]);

        return response()->json([
            'mails' => MailResource::collection($mails),
            'pagination' => [
                'total' => $mails->total(),
                'per_page' => $mails->perPage(),
                'current_page' => $mails->currentPage(),
                'last_page' => $mails->lastPage()
            ]
        ]);
    }

    private function generatePreview(UploadedFile $file): ?string
    {
        Log::debug('Generating preview', [
            'filename' => $file->getClientOriginalName(),
            'type' => $file->getMimeType()
        ]);

        $preview = $this->filePreviewService->generatePreview($file);

        Log::debug('Preview generated', [
            'filename' => $file->getClientOriginalName(),
            'preview' => $preview ? 'success' : 'failed'
        ]);

        return $preview;
    }

    public function createLabel(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'required|string|max:50',
            'type' => 'required|string|in:system,custom'
        ]);

        $label = MailLabel::create($validated);

        return new MailLabelResource($label);
    }

    public function updateLabel(Request $request, $labelId)
    {
        $label = MailLabel::findOrFail($labelId);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'color' => 'sometimes|string|max:50'
        ]);

        $label->update($validated);

        return new MailLabelResource($label);
    }

    public function deleteLabel($labelId)
    {
        $label = MailLabel::findOrFail($labelId);
        $label->delete();

        return response()->noContent();
    }

    public function updateMailLabels(Request $request, $mailId)
    {
        $mail = Mail::findOrFail($mailId);

        $validated = $request->validate([
            'labelIds' => 'required|array',
            'labelIds.*' => 'exists:mail_labels,id'
        ]);

        $mail->labels()->sync($request->input('labelIds'));

        return new MailResource($mail->load('labels'));
    }
}
