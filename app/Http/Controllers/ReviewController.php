<?php

namespace App\Http\Controllers;

use App\Models\ProductReview;
use App\Http\Resources\ProductReviewResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $reviews = ProductReview::with('product')
            ->when($request->product_id, function($query) use ($request) {
                $query->where('product_id', $request->product_id);
            })
            ->latest()
            ->paginate($request->per_page ?? 10);

        return response()->json([
            'reviews' => ProductReviewResource::collection($reviews),
            'meta' => [
                'total' => $reviews->total(),
                'per_page' => $reviews->perPage(),
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
            ]
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'rating' => 'required|numeric|min:1|max:5',
            'comment' => 'required|string|min:10',
            'name' => 'required|string|max:255',
            'attachments' => 'nullable|array',
            'attachments.*' => 'nullable|string|url'
        ]);

        $review = ProductReview::create([
            ...$validated,
            'user_id' => Auth::id(),
            'posted_at' => now(),
            'is_purchased' => true, // You might want to check if user has purchased the product
            'helpful' => 0,
            'avatar_url' => Auth::user()->avatarUrl ?? null,
        ]);

        return response()->json([
            'message' => 'Review submitted successfully',
            'review' => new ProductReviewResource($review)
        ], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $review = ProductReview::findOrFail($id);
        
        // Check if user owns the review or is admin
        if ($review->user_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'rating' => 'required|numeric|min:1|max:5',
            'comment' => 'required|string|min:10',
            'attachments' => 'nullable|array',
            'attachments.*' => 'nullable|string|url'
        ]);

        $review->update($validated);

        return response()->json([
            'message' => 'Review updated successfully',
            'review' => new ProductReviewResource($review)
        ]);
    }

    public function helpful(string $id): JsonResponse
    {
        $review = ProductReview::findOrFail($id);
        $review->increment('helpful');

        return response()->json([
            'message' => 'Review marked as helpful',
            'helpful_count' => $review->helpful
        ]);
    }

    public function destroy(string $id): JsonResponse
    {
        $review = ProductReview::findOrFail($id);
        
        // Check if user owns the review or is admin
        if ($review->user_id !== Auth::id() && !Auth::user()->hasRole('admin')) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $review->delete();
        return response()->json(['message' => 'Review deleted successfully']);
    }
}
