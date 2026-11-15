<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    /**
     * List every administrator account with its post count.
     */
    public function index(): JsonResponse
    {
        $users = User::query()
            ->where('is_admin', true)
            ->withCount('posts')
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'created_at']);

        return response()->json($users);
    }

    /**
     * Create a new administrator account.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'is_admin' => true,
        ]);

        return response()->json([
            'message' => 'Admin berhasil ditambahkan.',
            'user' => $user->loadCount('posts'),
        ], 201);
    }

    /**
     * Update an administrator account. The password is optional.
     */
    public function update(Request $request, User $user): JsonResponse
    {
        abort_unless($user->is_admin, 404);

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ]);

        if (($validated['password'] ?? null) === null) {
            unset($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'message' => 'Admin diperbarui.',
            'user' => $user->fresh()->loadCount('posts'),
        ]);
    }

    /**
     * Delete an administrator account that no longer owns any post.
     */
    public function destroy(Request $request, User $user): JsonResponse
    {
        abort_unless($user->is_admin, 404);

        if ($request->user()->is($user)) {
            throw ValidationException::withMessages([
                'user' => 'Anda tidak dapat menghapus akun sendiri.',
            ]);
        }

        if ($user->posts()->withTrashed()->exists()) {
            throw ValidationException::withMessages([
                'user' => 'Admin ini masih memiliki artikel. Pindahkan atau hapus artikelnya terlebih dahulu.',
            ]);
        }

        $user->delete();

        return response()->json(['message' => 'Admin berhasil dihapus.']);
    }
}
