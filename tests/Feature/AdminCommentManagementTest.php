<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCommentManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_filters_comments_by_post_id(): void
    {
        $admin = User::factory()->admin()->create();
        $firstPost = Post::factory()->published()->create();
        $secondPost = Post::factory()->published()->create();
        $firstComment = Comment::factory()->approved()->create(['post_id' => $firstPost->id]);
        Comment::factory()->approved()->create(['post_id' => $secondPost->id]);

        $response = $this->actingAs($admin)
            ->getJson("/api/admin/comments?post_id={$firstPost->id}")
            ->assertOk();

        $this->assertSame([$firstComment->id], array_column($response->json('data'), 'id'));
    }

    public function test_index_returns_replies_collection(): void
    {
        $admin = User::factory()->admin()->create();
        $comment = Comment::factory()->approved()->create();
        Comment::factory()->authorReply()->create([
            'post_id' => $comment->post_id,
            'parent_id' => $comment->id,
        ]);

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/comments')
            ->assertOk();

        $this->assertCount(1, $response->json('data.0.replies'));
    }

    public function test_index_sorts_comments_and_replies_by_time(): void
    {
        $admin = User::factory()->admin()->create();
        $older = Comment::factory()->approved()->create(['created_at' => now()->subDays(2)]);
        $newer = Comment::factory()->approved()->create(['created_at' => now()->subDay()]);
        $olderReply = Comment::factory()->approved()->create([
            'post_id' => $older->post_id,
            'parent_id' => $older->id,
            'created_at' => now()->subDays(4),
        ]);
        $newerReply = Comment::factory()->approved()->create([
            'post_id' => $older->post_id,
            'parent_id' => $older->id,
            'created_at' => now()->subHours(2),
        ]);

        $descending = $this->actingAs($admin)
            ->getJson('/api/admin/comments')
            ->assertOk();

        $this->assertSame([$newer->id, $older->id], array_column($descending->json('data'), 'id'));
        $this->assertSame(
            [$newerReply->id, $olderReply->id],
            array_column($descending->json('data.1.replies'), 'id')
        );

        $ascending = $this->actingAs($admin)
            ->getJson('/api/admin/comments?sort=asc')
            ->assertOk();

        $this->assertSame([$older->id, $newer->id], array_column($ascending->json('data'), 'id'));
        $this->assertSame(
            [$olderReply->id, $newerReply->id],
            array_column($ascending->json('data.0.replies'), 'id')
        );
    }

    public function test_store_creates_top_level_author_comment(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Admin Utama', 'email' => 'admin@example.com']);
        $post = Post::factory()->published()->create();
        Setting::setValue('author_name', 'Godang');

        $this->actingAs($admin)
            ->postJson('/api/admin/comments', [
                'post_id' => $post->id,
                'body' => 'Catatan tambahan dari penulis.',
            ])
            ->assertCreated()
            ->assertJsonPath('comment.status', 'approved')
            ->assertJsonPath('comment.is_author', true)
            ->assertJsonPath('comment.name', 'Admin Utama')
            ->assertJsonPath('comment.email', 'admin@example.com')
            ->assertJsonPath('comment.post_id', $post->id)
            ->assertJsonPath('comment.parent_id', null);

        $this->assertDatabaseHas('comments', [
            'post_id' => $post->id,
            'name' => 'Admin Utama',
            'is_author' => true,
            'status' => 'approved',
        ]);
    }

    public function test_store_validates_required_fields(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->postJson('/api/admin/comments', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['post_id', 'body']);
    }

    public function test_store_rejects_unknown_post(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->postJson('/api/admin/comments', [
                'post_id' => 999,
                'body' => 'Komentar untuk post yang tidak ada.',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['post_id']);
    }

    public function test_update_changes_comment_body(): void
    {
        $admin = User::factory()->admin()->create();
        $comment = Comment::factory()->approved()->create(['body' => 'Teks lama.']);

        $this->actingAs($admin)
            ->putJson("/api/admin/comments/{$comment->id}", [
                'body' => 'Teks baru hasil edit admin.',
            ])
            ->assertOk()
            ->assertJsonPath('comment.body', 'Teks baru hasil edit admin.');

        $this->assertDatabaseHas('comments', ['id' => $comment->id, 'body' => 'Teks baru hasil edit admin.']);
    }

    public function test_update_rejects_oversized_body(): void
    {
        $admin = User::factory()->admin()->create();
        $comment = Comment::factory()->approved()->create();

        $this->actingAs($admin)
            ->putJson("/api/admin/comments/{$comment->id}", [
                'body' => str_repeat('a', 2001),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['body']);
    }

    public function test_comment_endpoints_require_admin(): void
    {
        $comment = Comment::factory()->approved()->create();

        $this->getJson('/api/admin/comments')->assertUnauthorized();
        $this->postJson('/api/admin/comments', ['post_id' => $comment->post_id, 'body' => 'x'])->assertUnauthorized();
        $this->putJson("/api/admin/comments/{$comment->id}", ['body' => 'x'])->assertUnauthorized();

        $member = User::factory()->create();
        $this->actingAs($member)->getJson('/api/admin/comments')->assertForbidden();
    }
}
