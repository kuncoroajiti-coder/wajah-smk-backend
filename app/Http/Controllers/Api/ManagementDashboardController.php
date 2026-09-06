<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ManagementDashboardController extends Controller
{
    public function index(Request $request)
    {
        $topSchoolsLimit = min(
            max($request->integer('top_schools', 10), 1),
            50
        );

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

        $schoolsWithReviews = Review::query()
            ->select('school_id')
            ->distinct()
            ->count('school_id');

        $topSchools = School::query()
            ->join('reviews', 'reviews.school_id', '=', 'schools.id')
            ->where('reviews.status', 'approved')
            ->select(
                'schools.id',
                'schools.npsn',
                'schools.name',
                'schools.province',
                DB::raw('COUNT(reviews.id) as review_count'),
                DB::raw('ROUND(AVG(reviews.rating), 2) as average_rating')
            )
            ->groupBy(
                'schools.id',
                'schools.npsn',
                'schools.name',
                'schools.province'
            )
            ->orderByDesc('review_count')
            ->orderByDesc('average_rating')
            ->limit($topSchoolsLimit)
            ->get();

        $reviewsByProvince = School::query()
            ->join('reviews', 'reviews.school_id', '=', 'schools.id')
            ->where('reviews.status', 'approved')
            ->select(
                'schools.province',
                DB::raw('COUNT(reviews.id) as review_count'),
                DB::raw('ROUND(AVG(reviews.rating), 2) as average_rating')
            )
            ->groupBy('schools.province')
            ->orderByDesc('review_count')
            ->get();

        return response()->json([
            'summary' => [
                'total' => $total,
                'pending' => $pending,
                'approved' => $approved,
                'rejected' => $rejected,
                'average_rating' => round((float) $averageRating, 2),
                'schools_with_reviews' => $schoolsWithReviews,
            ],
            'rating_distribution' => $ratingDistribution,
            'top_schools' => $topSchools,
            'reviews_by_province' => $reviewsByProvince,
        ]);
    }
}
