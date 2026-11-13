<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    /**
     * Sitemap with all public URLs.
     * GET /sitemap.xml
     */
    public function sitemap(): Response
    {
        $urls = [
            ['loc' => url('/'), 'lastmod' => now()->toIso8601String()],
            ['loc' => url('/blog'), 'lastmod' => now()->toIso8601String()],
            ['loc' => url('/about'), 'lastmod' => now()->toIso8601String()],
        ];

        Post::published()
            ->with('category')
            ->latest('published_at')
            ->each(function (Post $post) use (&$urls) {
                $urls[] = [
                    'loc' => url('/blog/'.$post->slug),
                    'lastmod' => $post->updated_at->toIso8601String(),
                ];
            });

        Category::each(function (Category $category) use (&$urls) {
            $urls[] = [
                'loc' => url("/category/{$category->slug}"),
                'lastmod' => now()->toIso8601String(),
            ];
        });

        Tag::each(function (Tag $tag) use (&$urls) {
            $urls[] = [
                'loc' => url("/tag/{$tag->slug}"),
                'lastmod' => now()->toIso8601String(),
            ];
        });

        return response()
            ->view('seo.sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    /**
     * Robots crawler instructions.
     * GET /robots.txt
     */
    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Disallow: /api/',
            'Disallow: /admin',
            '',
            'Sitemap: '.url('/sitemap.xml'),
        ];

        return response(implode("\n", $lines), 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
        ]);
    }

    /**
     * RSS feed.
     * GET /feed.xml
     */
    public function feed(): Response
    {
        $posts = Post::published()
            ->latest('published_at')
            ->take(20)
            ->get();

        return response()
            ->view('seo.feed', ['posts' => $posts])
            ->header('Content-Type', 'application/rss+xml; charset=UTF-8');
    }
}
