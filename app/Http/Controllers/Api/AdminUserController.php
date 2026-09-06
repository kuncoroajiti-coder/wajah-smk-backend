<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = trim(
                    $request->string('search')->toString()
                );

                $query->where(function ($q) use ($search) {
                    $q->where('name', 'ilike', "%{$search}%")
                        ->orWhere('nip', 'ilike', "%{$search}%");
                });
            })
            ->when($request->filled('role'), function ($query) use ($request) {
                $query->where('role', $request->input('role'));
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                $query->where('status', $request->input('status'));
            })
            ->orderBy('name')
            ->paginate(
                min($request->integer('per_page', 20), 50)
            );

        return response()->json($users);
    }

    public function show(User $user)
    {
        return response()->json([
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'nip' => $user->nip,
                'role' => $user->role,
                'status' => $user->status,
                'must_change_password' => $user->must_change_password,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ],
        ]);
    }

    public function toggleStatus(Request $request, User $user)
    {
        $currentUser = $request->user();

        if ($currentUser->id === $user->id) {
            return response()->json([
                'message' => 'Anda tidak dapat mengubah status akun sendiri.',
            ], 422);
        }

        $newStatus = $user->status === 'aktif'
            ? 'nonaktif'
            : 'aktif';

        $user->update([
            'status' => $newStatus,
        ]);

        if ($newStatus === 'nonaktif') {
            $user->tokens()->delete();
        }

        return response()->json([
            'message' => $newStatus === 'aktif'
                ? 'Akun berhasil diaktifkan.'
                : 'Akun berhasil dinonaktifkan.',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'nip' => $user->nip,
                'role' => $user->role,
                'status' => $user->status,
                'must_change_password' => $user->must_change_password,
            ],
        ]);
    }

    public function resetPassword(User $user)
    {
        $user->update([
            'password' => '12345',
            'must_change_password' => true,
        ]);

        $user->tokens()->delete();

        return response()->json([
            'message' => 'Password berhasil direset ke password awal.',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'nip' => $user->nip,
                'role' => $user->role,
                'status' => $user->status,
                'must_change_password' => $user->must_change_password,
            ],
        ]);
    }

    public function updateRole(Request $request, User $user)
    {
        $currentUser = $request->user();

        if ($currentUser->id === $user->id) {
            return response()->json([
                'message' => 'Anda tidak dapat mengubah role akun sendiri.',
            ], 422);
        }

        $validated = $request->validate([
            'role' => [
                'required',
                'string',
                Rule::in([
                    'pegawai_boe',
                    'manajemen',
                    'super_admin',
                ]),
            ],
        ]);

        $user->update([
            'role' => $validated['role'],
        ]);

        return response()->json([
            'message' => 'Role akun berhasil diperbarui.',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'nip' => $user->nip,
                'role' => $user->role,
                'status' => $user->status,
                'must_change_password' => $user->must_change_password,
            ],
        ]);
    }
}
