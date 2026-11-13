<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicController extends Controller
{
    /**
     * Landing page.
     * GET /
     */
    public function landing(): View
    {
        $latestPosts = Post::published()
            ->with(['category', 'tags'])
            ->latest('published_at')
            ->take(6)
            ->get();

        $categories = Category::withCount([
            'posts' => fn ($q) => $q->published(),
        ])->get();

        return view('public.landing', [
            'latestPosts' => $latestPosts,
            'categories' => $categories,
        ]);
    }

    /**
     * Blog listing.
     * GET /blog
     */
    public function blogIndex(Request $request): View
    {
        $query = Post::published()
            ->with(['category', 'tags'])
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

        $posts = $query->paginate(12);
        $categories = Category::all();

        return view('public.blog.index', [
            'posts' => $posts,
            'categories' => $categories,
        ]);
    }

    /**
     * Blog detail.
     * GET /blog/{slug}
     */
    public function blogShow(string $slug): View
    {
        $post = Post::published()
            ->where('slug', $slug)
            ->with(['category', 'tags', 'author'])
            ->firstOrFail();

        $post->increment('views_count');

        $allComments = $post->comments()
            ->approved()
            ->latest()
            ->get();

        $totalComments = $allComments->count();

        $comments = $allComments->filter(fn ($comment) => is_null($comment->parent_id))
            ->values()
            ->map(function ($comment) use ($allComments) {
                $comment->setRelation(
                    'replies',
                    $allComments->filter(fn ($c) => $c->parent_id == $comment->id)->values()
                );

                return $comment;
            });

        $relatedPosts = Post::published()
            ->where('id', '!=', $post->id)
            ->when($post->category_id, fn ($q) => $q->where('category_id', $post->category_id))
            ->latest('published_at')
            ->take(2)
            ->get();

        return view('public.blog.show', [
            'post' => $post,
            'comments' => $comments,
            'totalComments' => $totalComments,
            'relatedPosts' => $relatedPosts,
        ]);
    }

    /**
     * About page.
     * GET /about
     */
    public function about(): View
    {
        return view('public.about');
    }

    /**
     * Category listing.
     * GET /category/{slug}
     */
    public function category(string $slug): View
    {
        $category = Category::where('slug', $slug)->firstOrFail();

        $posts = Post::published()
            ->where('category_id', $category->id)
            ->with(['category', 'tags'])
            ->latest('published_at')
            ->paginate(12);

        return view('public.category', [
            'category' => $category,
            'posts' => $posts,
        ]);
    }

    /**
     * Tag listing.
     * GET /tag/{slug}
     */
    public function tag(string $slug): View
    {
        $tag = Tag::where('slug', $slug)->firstOrFail();

        $posts = Post::published()
            ->whereHas('tags', fn ($q) => $q->where('tags.id', $tag->id))
            ->with(['category', 'tags'])
            ->latest('published_at')
            ->paginate(12);

        return view('public.tag', [
            'tag' => $tag,
            'posts' => $posts,
        ]);
    }
}
