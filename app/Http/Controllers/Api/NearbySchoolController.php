<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\School;
use Illuminate\Http\Request;

class NearbySchoolController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius' => ['nullable', 'numeric', 'min:1', 'max:100'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:20'],
        ]);

        $latitude = (float) $validated['latitude'];
        $longitude = (float) $validated['longitude'];
        $radius = (float) ($validated['radius'] ?? 25);
        $limit = (int) ($validated['limit'] ?? 10);

        // Bounding box awal agar PostgreSQL tidak menghitung
        // jarak untuk seluruh sekolah.
        $latitudeDelta = $radius / 111.32;

        $cosLatitude = max(
            abs(cos(deg2rad($latitude))),
            0.01
        );

        $longitudeDelta = $radius / (111.32 * $cosLatitude);

        $minLatitude = max(-90, $latitude - $latitudeDelta);
        $maxLatitude = min(90, $latitude + $latitudeDelta);

        $minLongitude = max(-180, $longitude - $longitudeDelta);
        $maxLongitude = min(180, $longitude + $longitudeDelta);

        // Rumus Haversine dalam kilometer.
        $distanceExpression = <<<'SQL'
            6371 * 2 * ASIN(
                SQRT(
                    POWER(SIN(RADIANS(latitude - ?) / 2), 2) +
                    COS(RADIANS(?)) *
                    COS(RADIANS(latitude)) *
                    POWER(SIN(RADIANS(longitude - ?) / 2), 2)
                )
            )
        SQL;

        $schools = School::query()
            ->where('is_active', true)
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereBetween('latitude', [$minLatitude, $maxLatitude])
            ->whereBetween('longitude', [$minLongitude, $maxLongitude])
            ->select([
                'id',
                'npsn',
                'name',
                'province',
                'city',
                'district',
                'village',
                'accreditation',
                'latitude',
                'longitude',
            ])
            ->selectRaw(
                "{$distanceExpression} AS distance_km",
                [$latitude, $latitude, $longitude]
            )
            ->whereRaw(
                "{$distanceExpression} <= ?",
                [$latitude, $latitude, $longitude, $radius]
            )
            ->orderBy('distance_km')
            ->limit($limit)
            ->get();

        return response()->json([
            'user_location' => [
                'latitude' => $latitude,
                'longitude' => $longitude,
            ],
            'radius_km' => $radius,
            'data' => $schools,
            'total' => $schools->count(),
        ]);
    }
}
