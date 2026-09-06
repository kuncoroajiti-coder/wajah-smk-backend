<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        return response()->json([
            'message' => 'Registrasi mandiri tidak tersedia. Akses Wajah SMK hanya untuk pegawai yang terdaftar.',
        ], 403);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'nip' => ['required', 'string', 'max:30'],
            'password' => ['required', 'string'],
        ]);

        $nip = preg_replace('/\D+/', '', $validated['nip']);

        $user = User::where('nip', $nip)->first();

        if (!$user) {
            throw ValidationException::withMessages([
                'nip' => ['NIP tidak terdaftar dalam daftar pegawai Wajah SMK.'],
            ]);
        }

        if ($user->status !== 'aktif') {
            throw ValidationException::withMessages([
                'nip' => ['Akun Anda berstatus nonaktif. Silakan hubungi Super Admin.'],
            ]);
        }

        if (!Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'nip' => ['NIP atau password salah.'],
            ]);
        }

        /*
         * Satu akun hanya mempertahankan token login terbaru.
         * Token lama dicabut untuk mengurangi sesi aktif yang tidak diperlukan.
         */
        $user->tokens()->delete();

        $token = $user->createToken('wajah-smk')->plainTextToken;

        return response()->json([
            'message' => 'Login berhasil.',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'nip' => $user->nip,
                'role' => $user->role,
                'status' => $user->status,
                'must_change_password' => $user->must_change_password,
            ],
            'token' => $token,
        ]);
    }

    public function logout(Request $request)
    {
        $token = $request->user()->currentAccessToken();

        if ($token) {
            $token->delete();
        }

        return response()->json([
            'message' => 'Logout berhasil.',
        ]);
    }
}
