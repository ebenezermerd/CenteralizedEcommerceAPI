<?php

namespace App\Http\Controllers;

use App\Models\ProductReview;
use App\Http\Resources\ProductReviewResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ReviewController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $reviews = ProductReview::with('product')
            ->when($request->product_id, function ($query) use ($request) {
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
        $user = auth()->user();

        if (!$user->hasRole('customer')) {
            return response()->json(['message' => 'Unauthorized to create a review'], 403);
        }

        // Validate the incoming request
        $validated = $request->validate([
            'comment' => 'required|string|max:1000',
            'name' => 'required|string|max:255',
            'product_id' => 'required|uuid|exists:products,id',
            'rating' => 'required|integer|between:1,5',
        ]);

        try {
            // Create a new review
            $review = ProductReview::create([
                'comment' => $validated['comment'],
                'name' => $validated['name'],
                'product_id' => $validated['product_id'],
                'avatar_url' => auth()->user()->avatarUrl,
                'rating' => $validated['rating'],
                'user_id' => auth()->id(), // Assuming you want to associate the review with the authenticated user
            ]);

            Log::info('Review created successfully', ['review_id' => $review->id]);

            return response()->json([
                'message' => 'Review created successfully',
                'review' => $review,
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create review', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Failed to create review'], 500);
        }
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
