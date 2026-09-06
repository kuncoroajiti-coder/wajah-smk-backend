<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\School;
use Illuminate\Http\Request;

class AdminSchoolController extends Controller
{
    public function index(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Opsi Filter Wilayah
        |--------------------------------------------------------------------------
        |
        | Dipanggil frontend menggunakan:
        | /admin/schools?filter_options=1
        |
        | Province -> City -> District dibuat bertingkat
        | berdasarkan data aktual di database.
        |
        */

        if ($request->boolean('filter_options')) {
            $province = trim(
                $request->string('province')->toString()
            );

            $city = trim(
                $request->string('city')->toString()
            );

            $provinces = School::query()
                ->whereNotNull('province')
                ->where('province', '!=', '')
                ->select('province')
                ->distinct()
                ->orderBy('province')
                ->pluck('province')
                ->values();

            $cities = collect();

            if ($province !== '') {
                $cities = School::query()
                    ->where('province', $province)
                    ->whereNotNull('city')
                    ->where('city', '!=', '')
                    ->select('city')
                    ->distinct()
                    ->orderBy('city')
                    ->pluck('city')
                    ->values();
            }

            $districts = collect();

            if ($province !== '' && $city !== '') {
                $districts = School::query()
                    ->where('province', $province)
                    ->where('city', $city)
                    ->whereNotNull('district')
                    ->where('district', '!=', '')
                    ->select('district')
                    ->distinct()
                    ->orderBy('district')
                    ->pluck('district')
                    ->values();
            }

            $statuses = School::query()
                ->whereNotNull('status')
                ->where('status', '!=', '')
                ->select('status')
                ->distinct()
                ->orderBy('status')
                ->pluck('status')
                ->values();

            return response()->json([
                'provinces' => $provinces,
                'cities' => $cities,
                'districts' => $districts,
                'statuses' => $statuses,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Daftar Sekolah
        |--------------------------------------------------------------------------
        */

        $schools = School::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim(
                    $request->string('search')->toString()
                );

                $query->where(function ($q) use ($search) {
                    $q->where('name', 'ilike', "%{$search}%")
                        ->orWhere('npsn', 'ilike', "%{$search}%");
                });
            })
            ->when($request->filled('province'), function ($query) use ($request) {
                $query->where(
                    'province',
                    $request->input('province')
                );
            })
            ->when($request->filled('city'), function ($query) use ($request) {
                $query->where(
                    'city',
                    $request->input('city')
                );
            })
            ->when($request->filled('district'), function ($query) use ($request) {
                $query->where(
                    'district',
                    $request->input('district')
                );
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where(
                    'status',
                    $request->input('status')
                );
            })
            ->when($request->has('is_active'), function ($query) use ($request) {
                $query->where(
                    'is_active',
                    filter_var(
                        $request->input('is_active'),
                        FILTER_VALIDATE_BOOLEAN
                    )
                );
            })
            ->withCount('reviews')
            ->orderBy('name')
            ->paginate(
                min($request->integer('per_page', 20), 50)
            );

        return response()->json($schools);
    }

    public function show(School $school)
    {
        $school->loadCount('reviews');

        return response()->json([
            'data' => $school,
        ]);
    }

    public function toggleActive(School $school)
    {
        $school->update([
            'is_active' => !$school->is_active,
        ]);

        return response()->json([
            'message' => $school->is_active
                ? 'Sekolah berhasil diaktifkan.'
                : 'Sekolah berhasil dinonaktifkan.',
            'data' => $school
                ->fresh()
                ->loadCount('reviews'),
        ]);
    }
}
