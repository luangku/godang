<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    /**
     * Dashboard statistics.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'total_posts' => Post::count(),
            'published_posts' => Post::published()->count(),
            'draft_posts' => Post::where('status', 'draft')->count(),
            'scheduled_posts' => Post::where('status', 'scheduled')->count(),
            'total_views' => Post::sum('views_count'),
            'pending_comments' => Comment::pending()->count(),
            'total_comments' => Comment::count(),
            'recent_posts' => Post::with('category')
                ->latest('updated_at')
                ->take(5)
                ->get(['id', 'title', 'slug', 'status', 'views_count', 'updated_at']),
            'recent_comments' => Comment::with('post:id,title,slug')
                ->latest()
                ->take(5)
                ->get(['id', 'post_id', 'name', 'body', 'status', 'is_author', 'created_at']),
        ]);
    }
}
