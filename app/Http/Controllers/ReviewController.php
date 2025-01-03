<?php

namespace App\Http\Controllers;

use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ReviewController extends Controller
{
    public function index(): JsonResponse
    {
        $reviews = Review::with('product')->paginate(10);
        return response()->json($reviews);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string',
        ]);

        $review = Review::create($validated);
        return response()->json($review, 201);
    }

    public function show(string $id): JsonResponse
    {
        $review = Review::with('product')->findOrFail($id);
        return response()->json($review);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string',
        ]);

        $review = Review::findOrFail($id);
        $review->update($validated);
        return response()->json($review);
    }

    public function destroy(string $id): JsonResponse
    {
        $review = Review::findOrFail($id);
        $review->delete();
        return response()->json(['message' => 'Review deleted successfully']);
    }
}
