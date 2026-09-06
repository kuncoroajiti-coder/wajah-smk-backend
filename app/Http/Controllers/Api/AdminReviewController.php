<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use Illuminate\Http\Request;

class AdminReviewController extends Controller
{
    public function pending(Request $request)
    {
        $reviews = Review::query()
            ->where('status', 'pending')
            ->with(['school', 'photos'])
            ->latest('created_at')
            ->paginate(
                min($request->integer('per_page', 20), 50)
            );

        return response()->json($reviews);
    }

    public function approve(Review $review)
    {
        $review->update([
            'status' => 'approved',
            'published_at' => now(),
        ]);

        return response()->json([
            'message' => 'Ulasan berhasil disetujui.',
            'data' => $review->load(['school', 'photos']),
        ]);
    }

    public function reject(Review $review)
    {
        $review->update([
            'status' => 'rejected',
            'published_at' => null,
        ]);

        return response()->json([
            'message' => 'Ulasan berhasil ditolak.',
            'data' => $review->load(['school', 'photos']),
        ]);
    }

    public function statistics()
    {
        $total = Review::count();

        $pending = Review::where('status', 'pending')->count();

        $approved = Review::where('status', 'approved')->count();

        $rejected = Review::where('status', 'rejected')->count();

        $averageRating = Review::where('status', 'approved')
            ->avg('rating');

        $ratingDistribution = [];

        for ($rating = 5; $rating >= 1; $rating--) {
            $ratingDistribution[$rating] = Review::query()
                ->where('status', 'approved')
                ->where('rating', $rating)
                ->count();
        }

        return response()->json([
            'total' => $total,
            'pending' => $pending,
            'approved' => $approved,
            'rejected' => $rejected,
            'average_rating' => round((float) $averageRating, 2),
            'rating_distribution' => $ratingDistribution,
        ]);
    }
}
