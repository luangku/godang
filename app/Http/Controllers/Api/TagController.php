<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;

class TagController extends Controller
{
    /**
     * List all tags.
     */
    public function index(): JsonResponse
    {
        $tags = Tag::withCount(['posts' => fn ($q) => $q->published()])
            ->orderBy('slug')
            ->get();

        return response()->json($tags);
    }
}
