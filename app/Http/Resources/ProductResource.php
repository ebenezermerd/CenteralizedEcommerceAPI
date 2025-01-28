<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => (string) $this->id,
            'name' => $this->name,
            'sku' => $this->sku,
            'code' => $this->code,
            'description' => $this->description,
            'subDescription' => $this->subDescription,
            'publish' => $this->publish ?? 'draft',

            // Media (updated with full URLs)
            'coverUrl' => $this->coverUrl ? (str_starts_with($this->coverUrl, 'http') ? $this->coverUrl : url(Storage::url($this->coverUrl))) : null,
            'images' => $this->images->pluck('image_path')->map(fn($path) => url(Storage::url($path))),

            // Pricing
            'price' => (float) $this->price,
            'priceSale' => (float) $this->priceSale,
            'taxes' => (float) $this->taxes,

            // Attributes
            'tags' => $this->tags ?? [],
            'sizes' => $this->sizes ?? [],
            'colors' => $this->colors ?? [],
            'gender' => $this->gender ?? [],

            // Inventory
            'inventoryType' => $this->inventoryType,
            'quantity' => $this->quantity,
            'available' => $this->available,
            'totalSold' => $this->totalSold,

            // Category
            'category' => $this->category?->name,

            // Reviews and Ratings
            'totalRatings' => (float) $this->average_rating,
            'totalReviews' => (int) $this->reviews_count,
            'reviews' => $this->reviews->map(function($review) {
                return [
                    'id' => (string) $review->id,
                    'name' => $review->name,
                    'postedAt' => $review->posted_at->toIso8601String(),
                    'comment' => $review->comment,
                    'isPurchased' => $review->is_purchased,
                    'rating' => (float) $review->rating,
                    'avatarUrl' => $review->avatar_url,
                    'helpful' => (int) $review->helpful,
                    'attachments' => $review->attachments ?? []
                ];
            }),
            'ratings' => collect(range(1, 5))->map(function($star) {
                $reviews = $this->reviews->where('rating', $star);
                return [
                    'name' => "$star Star",
                    'starCount' => $reviews->count(),
                    'reviewCount' => $reviews->sum('helpful')
                ];
            })->values(),

            // Labels
            'newLabel' => [
                'enabled' => !empty($this->newLabel),
                'content' => $this->newLabel['content'] ?? 'NEW'
            ],
            'saleLabel' => [
                'enabled' => !empty($this->saleLabel),
                'content' => $this->saleLabel['content'] ?? 'SALE'
            ],

            'createdAt' => $this->created_at
        ];
    }
}
