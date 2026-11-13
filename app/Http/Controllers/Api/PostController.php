<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PostController extends Controller
{
    /**
     * List published posts with optional filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Post::published()
            ->with(['category', 'tags', 'author:id,name'])
            ->latest('published_at');

        if ($request->filled('q')) {
            $search = $request->q;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('excerpt', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category')) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $request->category));
        }

        if ($request->filled('tag')) {
            $query->whereHas('tags', fn ($q) => $q->where('slug', $request->tag));
        }

        $perPage = max(1, min(50, $request->integer('per_page', 12)));
        $posts = $query->paginate($perPage);

        return response()->json($posts);
    }

    /**
     * Get a single published post by slug.
     */
    public function show(string $slug): JsonResponse
    {
        $query = Post::published()
            ->with(['category', 'tags', 'author:id,name,avatar,bio']);

        $post = $query->where('slug', $slug)->firstOrFail();

        return response()->json($post);
    }
}
