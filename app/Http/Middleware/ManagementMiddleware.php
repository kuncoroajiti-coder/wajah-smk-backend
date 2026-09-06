<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ManagementMiddleware
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

        if (!in_array($user->role, ['manajemen', 'super_admin'], true)) {
            return response()->json([
                'message' => 'Akses hanya untuk Manajemen atau Super Admin.',
            ], 403);
        }

        return $next($request);
    }
}
