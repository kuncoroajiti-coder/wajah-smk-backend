<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PasswordChangeMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], 401);
        }

        if ($user->status !== 'aktif') {
            return response()->json([
                'message' => 'Akun Anda berstatus nonaktif.',
            ], 403);
        }

        if ($user->must_change_password) {
            return response()->json([
                'message' => 'Anda wajib mengganti password sebelum menggunakan fitur lain.',
                'must_change_password' => true,
            ], 403);
        }

        return $next($request);
    }
}
