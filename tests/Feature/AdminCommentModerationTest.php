<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCommentModerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_approve_sets_comment_status_to_approved(): void
    {
        $admin = User::factory()->admin()->create();
        $comment = Comment::factory()->pending()->create();

        $this->actingAs($admin)
            ->patchJson("/api/admin/comments/{$comment->id}")
            ->assertOk()
            ->assertJsonPath('comment.status', 'approved');

        $this->assertDatabaseHas('comments', ['id' => $comment->id, 'status' => 'approved']);
    }

    public function test_mark_spam_cascades_to_replies(): void
    {
        $admin = User::factory()->admin()->create();
        $comment = Comment::factory()->approved()->create();
        $reply = Comment::factory()->authorReply()->create([
            'post_id' => $comment->post_id,
            'parent_id' => $comment->id,
        ]);

        $this->actingAs($admin)
            ->patchJson("/api/admin/comments/{$comment->id}/spam")
            ->assertOk()
            ->assertJsonPath('comment.status', 'spam');

        $this->assertDatabaseHas('comments', ['id' => $comment->id, 'status' => 'spam']);
        $this->assertDatabaseHas('comments', ['id' => $reply->id, 'status' => 'spam']);
    }

    public function test_reply_creates_approved_author_comment(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Admin Utama']);
        $comment = Comment::factory()->approved()->create();
        Setting::setValue('author_name', 'Godang');

        $this->actingAs($admin)
            ->postJson("/api/admin/comments/{$comment->id}/reply", [
                'body' => 'Ini jawaban dari penulis.',
            ])
            ->assertCreated()
            ->assertJsonPath('reply.status', 'approved')
            ->assertJsonPath('reply.is_author', true)
            ->assertJsonPath('reply.name', 'Admin Utama')
            ->assertJsonPath('reply.parent_id', $comment->id);
    }

    public function test_destroy_removes_comment_and_replies(): void
    {
        $admin = User::factory()->admin()->create();
        $comment = Comment::factory()->approved()->create();
        $reply = Comment::factory()->authorReply()->create([
            'post_id' => $comment->post_id,
            'parent_id' => $comment->id,
        ]);

        $this->actingAs($admin)
            ->deleteJson("/api/admin/comments/{$comment->id}")
            ->assertOk();

        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
        $this->assertDatabaseMissing('comments', ['id' => $reply->id]);
    }

    public function test_bulk_delete_removes_comments_and_their_replies(): void
    {
        $admin = User::factory()->admin()->create();
        $first = Comment::factory()->approved()->create();
        $firstReply = Comment::factory()->authorReply()->create([
            'post_id' => $first->post_id,
            'parent_id' => $first->id,
        ]);
        $second = Comment::factory()->pending()->create();

        $this->actingAs($admin)
            ->postJson('/api/admin/comments/bulk-delete', [
                'ids' => [$first->id, $second->id],
            ])
            ->assertOk();

        $this->assertDatabaseMissing('comments', ['id' => $first->id]);
        $this->assertDatabaseMissing('comments', ['id' => $firstReply->id]);
        $this->assertDatabaseMissing('comments', ['id' => $second->id]);
    }

    public function test_bulk_delete_validates_ids(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->postJson('/api/admin/comments/bulk-delete', ['ids' => [999]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['ids.0']);
    }

    public function test_index_filters_comments_by_status(): void
    {
        $admin = User::factory()->admin()->create();
        Comment::factory()->pending()->create();
        Comment::factory()->approved()->create();

        $this->actingAs($admin)
            ->getJson('/api/admin/comments?status=pending')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'pending');
    }
}
