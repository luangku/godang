<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PostController extends Controller
{
    /**
     * List all posts (admin view, includes drafts).
     */
    public function index(Request $request): JsonResponse
    {
        $query = Post::with(['category', 'tags', 'author:id,name'])
            ->latest('updated_at');

        if ($request->boolean('trashed')) {
            $query->onlyTrashed();
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('q')) {
            $search = $request->q;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('excerpt', 'like', "%{$search}%");
            });
        }

        $posts = $query->paginate($this->perPage($request, 15));

        return response()->json($posts);
    }

    /**
     * Create a new post.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('posts', 'slug')],
            'category_id' => ['nullable', 'exists:categories,id'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['nullable', 'string'],
            'cover_image' => ['nullable', 'string'],
            'og_image' => ['nullable', 'string'],
            'status' => ['required', 'string', 'in:draft,published,scheduled'],
            'published_at' => ['nullable', 'date'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['nullable'],
        ]);

        $validated['user_id'] = $request->user()->id;

        if (empty($validated['slug'])) {
            $validated['slug'] = $this->uniqueSlug(Str::slug($validated['title']));
        }

        if ($validated['status'] === 'published' && empty($validated['published_at'])) {
            $validated['published_at'] = now();
        }

        $tags = $validated['tags'] ?? [];
        unset($validated['tags']);

        $post = Post::create($validated);

        $this->syncTags($post, $tags);

        return response()->json($post->load(['category', 'tags']), 201);
    }

    /**
     * Show a single post.
     */
    public function show(Post $post): JsonResponse
    {
        $post->load(['category', 'tags', 'author:id,name']);

        return response()->json($post);
    }

    /**
     * Update a post.
     */
    public function update(Request $request, Post $post): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('posts', 'slug')->ignore($post->id)],
            'category_id' => ['nullable', 'exists:categories,id'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'content' => ['nullable', 'string'],
            'cover_image' => ['nullable', 'string'],
            'og_image' => ['nullable', 'string'],
            'status' => ['sometimes', 'string', 'in:draft,published,scheduled'],
            'published_at' => ['nullable', 'date'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['nullable'],
        ]);

        if (array_key_exists('slug', $validated) && $validated['slug'] === null) {
            unset($validated['slug']);
        }

        if (isset($validated['status']) && $validated['status'] === 'published' && empty($validated['published_at']) && ! $post->published_at) {
            $validated['published_at'] = now();
        }

        $tags = $validated['tags'] ?? null;
        unset($validated['tags']);

        $post->update($validated);

        if ($tags !== null) {
            $this->syncTags($post, $tags);
        }

        return response()->json($post->load(['category', 'tags']));
    }

    /**
     * Soft-delete multiple posts, or permanently when force is set.
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:posts,id'],
            'force' => ['sometimes', 'boolean'],
        ]);

        $count = count($validated['ids']);

        if ($request->boolean('force')) {
            Post::withTrashed()
                ->whereIn('id', $validated['ids'])
                ->get()
                ->each(fn (Post $post) => $post->forceDelete());

            return response()->json(['message' => "{$count} artikel dihapus permanen."]);
        }

        Post::whereIn('id', $validated['ids'])->delete();

        return response()->json(['message' => "{$count} artikel dipindahkan ke sampah."]);
    }

    /**
     * Restore a soft-deleted post.
     */
    public function restore(Post $post): JsonResponse
    {
        $post->restore();

        return response()->json(['message' => 'Artikel berhasil dipulihkan.', 'post' => $post]);
    }

    /**
     * Soft-delete a post, or permanently when force is set.
     */
    public function destroy(Request $request, Post $post): JsonResponse
    {
        if ($request->boolean('force')) {
            $post->forceDelete();

            return response()->json(['message' => 'Artikel dihapus permanen.']);
        }

        $post->delete();

        return response()->json(['message' => 'Artikel dipindahkan ke sampah.']);
    }

    /**
     * Attach the submitted tags, creating any that do not exist yet.
     *
     * @param  array<int, mixed>  $tags
     */
    private function syncTags(Post $post, array $tags): void
    {
        $tagIds = [];

        foreach ($tags as $tag) {
            if (is_numeric($tag)) {
                $tagIds[] = (int) $tag;

                continue;
            }

            if (is_array($tag) && isset($tag['id']) && is_numeric($tag['id'])) {
                $tagIds[] = (int) $tag['id'];

                continue;
            }

            $tagName = null;
            if (is_array($tag)) {
                $tagName = $tag['name'] ?? $tag['value'] ?? $tag['label'] ?? null;
            } elseif (is_string($tag) || is_scalar($tag)) {
                $tagName = (string) $tag;
            }

            if (! $tagName) {
                continue;
            }

            $tagName = strip_tags((string) $tagName);
            $tagName = trim($tagName);
            $tagName = preg_replace('/\s+/', ' ', $tagName);

            if ($tagName === '') {
                continue;
            }

            if (mb_strlen($tagName) > 50) {
                $tagName = mb_substr($tagName, 0, 50);
            }

            $slugTag = Str::slug($tagName);
            if ($slugTag === '') {
                $slugTag = Str::slug(str_replace(['<', '>', '/', '\\'], ' ', $tagName));
            }

            if ($slugTag === '') {
                continue;
            }

            $existing = Tag::where('slug', $slugTag)->first();
            if ($existing) {
                $tagIds[] = $existing->id;

                continue;
            }

            $created = Tag::create([
                'name' => $tagName,
                'slug' => $slugTag,
            ]);
            $tagIds[] = $created->id;
        }

        $post->tags()->sync(array_values(array_unique($tagIds)));
    }

    /**
     * Clamp the requested page size between 1 and 50.
     */
    private function perPage(Request $request, int $default): int
    {
        return max(1, min(50, $request->integer('per_page', $default)));
    }

    /**
     * Find a slug that is free (including soft-deleted rows).
     */
    private function uniqueSlug(string $slug, ?int $ignoreId = null): string
    {
        $base = $slug !== '' ? $slug : 'post';
        $candidate = $base;
        $suffix = 2;

        while (Post::withTrashed()
            ->where('slug', $candidate)
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists()) {
            $candidate = $base.'-'.$suffix++;
        }

        return $candidate;
    }
}
