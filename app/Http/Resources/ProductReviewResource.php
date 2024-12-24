<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductReviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'name' => $this->name,
            'rating' => (float) $this->rating,
            'comment' => $this->comment,
            'helpful' => $this->helpful ?? 0,
            'avatarUrl' => $this->avatar_url ?? '',
            'postedAt' => $this->posted_at ?? $this->created_at,
            'isPurchased' => $this->is_purchased ?? false,
            'attachments' => $this->attachments ?? []
        ];
    }
}
