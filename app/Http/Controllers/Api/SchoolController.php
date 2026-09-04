<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\School;
use Illuminate\Http\Request;

class SchoolController extends Controller
{
    public function index(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Opsi Filter Wilayah
        |--------------------------------------------------------------------------
        |
        | Digunakan oleh halaman daftar sekolah publik.
        | Province -> City -> District dibuat bertingkat
        | berdasarkan data sekolah aktif di database.
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
                ->where('is_active', true)
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
                    ->where('is_active', true)
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
                    ->where('is_active', true)
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
                ->where('is_active', true)
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
        | Daftar Sekolah Publik
        |--------------------------------------------------------------------------
        */

        $schools = School::query()
            ->where('is_active', true)
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
            ->withCount('reviews')
            ->orderBy('name')
            ->paginate(
                $request->integer('per_page', 20)
            );

        return response()->json($schools);
    }

    public function show(School $school)
    {
        $school->load('programs');

        return response()->json($school);
    }
}