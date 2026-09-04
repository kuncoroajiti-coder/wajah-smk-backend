<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\ReviewPhoto;
use App\Models\School;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function index(Request $request, School $school)
    {
        $reviews = $school->reviews()
            ->where('status', 'approved')
            ->with('photos')
            ->latest('published_at')
            ->latest('created_at')
            ->paginate($request->integer('per_page', 10));

        return response()->json($reviews);
    }

    public function publicIndex(Request $request)
    {
        $query = Review::query()
            ->where('status', 'approved')
            ->with([
                'school:id,npsn,name,province,city,district',
                'photos',
            ])
            ->latest('published_at')
            ->latest('created_at');

        if ($request->filled('search')) {
            $search = trim($request->string('search')->toString());

            $query->where(function ($reviewQuery) use ($search) {
                $reviewQuery
                    ->where('title', 'ilike', "%{$search}%")
                    ->orWhere('content', 'ilike', "%{$search}%")
                    ->orWhereHas('school', function ($schoolQuery) use ($search) {
                        $schoolQuery
                            ->where('name', 'ilike', "%{$search}%")
                            ->orWhere('npsn', 'ilike', "%{$search}%");
                    });
            });
        }

        if ($request->filled('rating')) {
            $rating = $request->integer('rating');

            if ($rating >= 1 && $rating <= 5) {
                $query->where('rating', $rating);
            }
        }

        $reviews = $query->paginate(
            $request->integer('per_page', 12)
        );

        return response()->json($reviews);
    }

    public function show(Review $review)
    {
        if ($review->status !== 'approved') {
            return response()->json([
                'message' => 'Ulasan tidak ditemukan.',
            ], 404);
        }

        $review->load([
            'school:id,npsn,name,province,city,district',
            'photos',
        ]);

        return response()->json([
            'data' => $review,
        ]);
    }

    public function store(Request $request, School $school)
    {
        $validated = $request->validate([
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title' => ['nullable', 'string', 'max:255'],
            'content' => ['required', 'string', 'min:10'],
            'photos' => ['nullable', 'array', 'max:5'],
            'photos.*' => [
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
        ]);

        $review = $school->reviews()->create([
            'user_id' => $request->user()->id,
            'rating' => $validated['rating'],
            'title' => $validated['title'] ?? null,
            'content' => $validated['content'],
            'status' => 'pending',
        ]);

        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $index => $photo) {
                $path = $photo->store('reviews', 'public');

                ReviewPhoto::create([
                    'review_id' => $review->id,
                    'path' => $path,
                    'caption' => null,
                    'sort_order' => $index,
                ]);
            }
        }

        $review->load('photos');

        return response()->json([
            'message' => 'Ulasan berhasil dikirim dan menunggu moderasi.',
            'data' => $review,
        ], 201);
    }

    public function myReviews(Request $request)
    {
        $reviews = Review::query()
            ->where('user_id', $request->user()->id)
            ->with([
                'school:id,npsn,name,province,city',
                'photos',
            ])
            ->latest('created_at')
            ->paginate($request->integer('per_page', 10));

        return response()->json($reviews);
    }
}