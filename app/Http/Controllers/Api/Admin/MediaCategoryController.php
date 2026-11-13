<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\MediaCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MediaCategoryController extends Controller
{
    /**
     * List media categories with the number of active media in each.
     */
    public function index(): JsonResponse
    {
        return response()->json(
            MediaCategory::query()
                ->withCount('media')
                ->orderBy('name')
                ->get()
        );
    }

    /**
     * Create a media category.
     */
    public function store(Request $request): JsonResponse
    {
        $request->merge(['name' => trim((string) $request->input('name'))]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:60', 'unique:media_categories,name'],
        ]);

        $category = MediaCategory::create($validated);

        return response()->json([
            'message' => 'Kategori media dibuat.',
            'category' => $category,
        ], 201);
    }

    /**
     * Rename a media category.
     */
    public function update(Request $request, MediaCategory $mediaCategory): JsonResponse
    {
        $request->merge(['name' => trim((string) $request->input('name'))]);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:60',
                Rule::unique('media_categories', 'name')->ignore($mediaCategory->id),
            ],
        ]);

        $mediaCategory->update($validated);

        return response()->json([
            'message' => 'Kategori media diperbarui.',
            'category' => $mediaCategory,
        ]);
    }

    /**
     * Delete a category. Existing media becomes uncategorized.
     */
    public function destroy(MediaCategory $mediaCategory): JsonResponse
    {
        $mediaCategory->delete();

        return response()->json(['message' => 'Kategori media dihapus. Media terkait menjadi tanpa kategori.']);
    }
}
