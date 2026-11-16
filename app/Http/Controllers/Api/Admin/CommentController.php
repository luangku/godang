<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    /**
     * List all comments with filters.
     */
    public function index(Request $request): JsonResponse
    {
        $sort = $request->query('sort') === 'asc' ? 'asc' : 'desc';

        $query = Comment::with([
            'post:id,title,slug',
            'parent:id,name,email,body',
            'replies' => fn ($q) => $q->orderBy('created_at', $sort)
                ->orderBy('id', $sort)
                ->with('parent:id,name'),
        ])
            ->withCount('replies')
            ->orderBy('created_at', $sort)
            ->orderBy('id', $sort);

        if ($request->filled('status')) {
            $status = $request->status;
            $query->where(function ($q) use ($status) {
                $q->where(function ($qq) use ($status) {
                    $qq->whereNull('parent_id')->where('status', $status);
                })
                    ->orWhere(function ($qq) use ($status) {
                        $qq->whereNotNull('parent_id')->where('status', $status);
                    });
            });
        } else {
            $query->whereNull('parent_id');
        }

        if ($request->filled('post_id')) {
            $query->where('post_id', $request->integer('post_id'));
        }

        if ($request->filled('category_id')) {
            $query->whereHas('post', fn ($q) => $q->where('category_id', $request->integer('category_id')));
        }

        $comments = $query->paginate($request->integer('per_page', 20));
        $comments->getCollection()->makeVisible(['email']);

        return response()->json($comments);
    }

    /**
     * Create a top-level comment on behalf of the admin.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'post_id' => ['required', 'integer', 'exists:posts,id'],
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $comment = Comment::create([
            'post_id' => $validated['post_id'],
            'name' => $request->user()->name,
            'email' => $request->user()->email,
            'body' => $validated['body'],
            'status' => 'approved',
            'is_author' => true,
        ]);

        return response()->json([
            'message' => 'Komentar berhasil dibuat.',
            'comment' => $comment->load('replies')->makeVisible('email'),
        ], 201);
    }

    /**
     * Edit a comment's body.
     */
    public function update(Request $request, Comment $comment): JsonResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $comment->update($validated);

        return response()->json([
            'message' => 'Komentar berhasil diperbarui.',
            'comment' => $comment->fresh()->load('replies')->makeVisible('email'),
        ]);
    }

    /**
     * Approve a comment.
     */
    public function approve(Comment $comment): JsonResponse
    {
        $comment->update(['status' => 'approved']);

        return response()->json(['message' => 'Komentar disetujui.', 'comment' => $comment->makeVisible('email')]);
    }

    /**
     * Mark a comment (and its replies) as spam.
     */
    public function markSpam(Comment $comment): JsonResponse
    {
        $comment->replies()->update(['status' => 'spam']);
        $comment->update(['status' => 'spam']);

        return response()->json(['message' => 'Komentar ditandai sebagai spam.', 'comment' => $comment->makeVisible('email')]);
    }

    /**
     * Reply to a comment as admin/author.
     */
    public function reply(Request $request, Comment $comment): JsonResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $reply = Comment::create([
            'post_id' => $comment->post_id,
            'parent_id' => $comment->parent_id ?? $comment->id,
            'reply_to_name' => $comment->name,
            'name' => $request->user()->name,
            'email' => $request->user()->email,
            'body' => $validated['body'],
            'status' => 'approved',
            'is_author' => true,
        ]);

        return response()->json(['message' => 'Balasan berhasil dikirim.', 'reply' => $reply], 201);
    }

    /**
     * Delete a comment and its replies.
     */
    public function destroy(Comment $comment): JsonResponse
    {
        $comment->replies()->delete();
        $comment->delete();

        return response()->json(['message' => 'Komentar berhasil dihapus.']);
    }

    /**
     * Bulk delete comments.
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:comments,id'],
        ]);

        // Delete replies first, then the comments themselves
        Comment::whereIn('parent_id', $validated['ids'])->delete();
        Comment::whereIn('id', $validated['ids'])->delete();

        return response()->json(['message' => count($validated['ids']).' komentar dihapus.']);
    }
}
