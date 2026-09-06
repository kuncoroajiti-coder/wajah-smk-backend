<?php

use App\Http\Controllers\Api\AdminReviewController;
use App\Http\Controllers\Api\AdminSchoolController;
use App\Http\Controllers\Api\AdminUserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ManagementDashboardController;
use App\Http\Controllers\Api\PasswordController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\SchoolController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);

Route::post('/login', [AuthController::class, 'login']);

Route::get('/schools', [SchoolController::class, 'index']);

Route::get('/schools/{school}', [SchoolController::class, 'show']);

Route::get('/schools/{school}/reviews', [ReviewController::class, 'index']);

Route::get('/reviews/{review}', [ReviewController::class, 'show']);

Route::get('/reviews', [ReviewController::class, 'publicIndex']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/change-password', [PasswordController::class, 'change']);

    Route::post('/logout', [AuthController::class, 'logout']);
});

Route::middleware([
    'auth:sanctum',
    'password.change',
])->group(function () {
    Route::post('/schools/{school}/reviews', [ReviewController::class, 'store']);

    Route::get('/my-reviews', [ReviewController::class, 'myReviews']);
});

Route::middleware([
    'auth:sanctum',
    'management',
    'password.change',
])->group(function () {
    Route::get(
        '/management/dashboard',
        [ManagementDashboardController::class, 'index']
    );
});

Route::middleware([
    'auth:sanctum',
    'admin',
    'password.change',
])->group(function () {
    /*
    |--------------------------------------------------------------------------
    | Admin - Statistik
    |--------------------------------------------------------------------------
    */

    Route::get('/admin/statistics', [AdminReviewController::class, 'statistics']);

    /*
    |--------------------------------------------------------------------------
    | Admin - Moderasi Ulasan
    |--------------------------------------------------------------------------
    */

    Route::get('/admin/reviews/pending', [AdminReviewController::class, 'pending']);

    Route::patch(
        '/admin/reviews/{review}/approve',
        [AdminReviewController::class, 'approve']
    );

    Route::patch(
        '/admin/reviews/{review}/reject',
        [AdminReviewController::class, 'reject']
    );

    /*
    |--------------------------------------------------------------------------
    | Admin - Manajemen Sekolah
    |--------------------------------------------------------------------------
    */

    Route::get('/admin/schools', [AdminSchoolController::class, 'index']);

    Route::get('/admin/schools/{school}', [AdminSchoolController::class, 'show']);

    Route::patch(
        '/admin/schools/{school}/toggle-active',
        [AdminSchoolController::class, 'toggleActive']
    );

    /*
    |--------------------------------------------------------------------------
    | Admin - Manajemen Pengguna
    |--------------------------------------------------------------------------
    */

    Route::get('/admin/users', [AdminUserController::class, 'index']);

    Route::get('/admin/users/{user}', [AdminUserController::class, 'show']);

    Route::patch(
        '/admin/users/{user}/toggle-status',
        [AdminUserController::class, 'toggleStatus']
    );

    Route::post(
        '/admin/users/{user}/reset-password',
        [AdminUserController::class, 'resetPassword']
    );

    Route::patch(
        '/admin/users/{user}/role',
        [AdminUserController::class, 'updateRole']
    );
});
