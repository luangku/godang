<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    /**
     * Update the authenticated admin's own profile.
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

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
            'avatar' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'avatar_alt' => ['sometimes', 'nullable', 'string', 'max:255'],
        ]);

        if (($validated['password'] ?? null) === null) {
            unset($validated['password']);
        }

        foreach (['avatar', 'avatar_alt'] as $key) {
            if (array_key_exists($key, $validated) && blank($validated[$key])) {
                $validated[$key] = null;
            }
        }

        $user->update($validated);

        return response()->json([
            'message' => 'Profil berhasil diperbarui.',
            'user' => $user->fresh(),
        ]);
    }
}
