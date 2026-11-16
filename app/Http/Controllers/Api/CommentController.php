<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\NewCommentNotification;
use App\Notifications\ReplyToCommentNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class CommentController extends Controller
{
    /**
     * Maximum number of links allowed in a comment body.
     */
    private const MAX_LINKS = 2;

    /**
     * List approved comments for a published post.
     */
    public function index(Post $post): JsonResponse
    {
        abort_unless($post->isPublished(), 404);

        $comments = $post->comments()
            ->approved()
            ->rootComments()
            ->with(['replies' => fn ($q) => $q->approved()->latest()])
            ->latest()
            ->get();

        return response()->json($comments);
    }

    /**
     * Submit a new comment on a published post.
     */
    public function store(Request $request, Post $post): JsonResponse
    {
        abort_unless($post->isPublished(), 404);

        // Honeypot check
        if ($request->filled('website')) {
            return response()->json(['message' => 'Spam terdeteksi.'], 422);
        }

        $admin = $request->user();
        $isAdmin = $admin !== null && (bool) $admin->is_admin;

        $rules = [
            'body' => ['required', 'string', 'max:2000'],
            'parent_id' => ['nullable', 'integer', 'exists:comments,id'],
            'notify_replies' => ['nullable', 'boolean'],
        ];

        if (! $isAdmin) {
            $rules['name'] = ['required', 'string', 'max:100'];
            $rules['email'] = ['required', 'email', 'max:255'];
        }

        $validated = $request->validate($rules);

        if (preg_match_all('#https?://#i', $validated['body']) > self::MAX_LINKS) {
            return response()->json([
                'message' => 'Komentar ditolak: terlalu banyak link di dalamnya.',
            ], 422);
        }

        $parentId = $validated['parent_id'] ?? null;
        $parent = null;
        $replyToName = null;

        if ($parentId) {
            $parent = Comment::find($parentId);
            if (! $parent || $parent->post_id !== $post->id) {
                return response()->json(['message' => 'Komentar induk tidak valid.'], 422);
            }

            // Keep the thread flat (2 levels): replying to a reply
            // re-parents the new comment under the root comment instead.
            $replyToName = $parent->name;
            $parentId = $parent->parent_id ?? $parent->id;
        }

        $notifyReplies = isset($validated['notify_replies']) ? (bool) $validated['notify_replies'] : false;

        $comment = $post->comments()->create([
            'name' => $isAdmin ? $admin->name : $validated['name'],
            'email' => $isAdmin ? $admin->email : $validated['email'],
            'body' => $validated['body'],
            'parent_id' => $parentId,
            'reply_to_name' => $replyToName,
            'notify_replies' => $notifyReplies,
            'status' => $isAdmin || ! $this->moderationRequired() ? 'approved' : 'pending',
            'is_author' => $isAdmin,
            'ip_hash' => hash('sha256', $request->ip()),
            'user_agent' => $request->userAgent(),
        ]);

        if (! $isAdmin) {
            $this->notifyAdmins($comment);
        }

        if ($parent && $parent->notify_replies && $parent->email) {
            try {
                Notification::route('mail', $parent->email)
                    ->notify(new ReplyToCommentNotification($comment));
            } catch (\Throwable $e) {
                // Ignore notification failures
            }
        }

        return response()->json([
            'message' => $comment->status === 'pending'
                ? 'Komentar berhasil dikirim dan menunggu persetujuan.'
                : 'Komentar berhasil dikirim.',
            'comment' => $comment,
        ], 201);
    }

    /**
     * Notify admin users about the new comment when notifications are enabled.
     */
    private function notifyAdmins(Comment $comment): void
    {
        $enabled = filter_var(
            Setting::getValue('comment_notification', 'true'),
            FILTER_VALIDATE_BOOL
        );

        if (! $enabled) {
            return;
        }

        $admins = User::where('is_admin', true)->get();

        try {
            Notification::send($admins, new NewCommentNotification($comment));
        } catch (\Throwable $e) {
            // Ignore notification failures so the comment still submits.
        }
    }

    /**
     * Determine whether new comments require moderation.
     */
    private function moderationRequired(): bool
    {
        return filter_var(
            Setting::getValue('comment_moderation', 'true'),
            FILTER_VALIDATE_BOOL
        );
    }
}
