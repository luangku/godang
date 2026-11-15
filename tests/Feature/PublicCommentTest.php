<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicCommentTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_creates_pending_comment_when_moderation_enabled(): void
    {
        $post = Post::factory()->published()->create();
        Setting::setValue('comment_moderation', 'true');

        $response = $this->postJson("/api/posts/{$post->id}/comments", [
            'name' => 'Pembaca',
            'email' => 'pembaca@example.com',
            'body' => 'Bagaimana cara mengatasi error ini?',
        ]);

        $response->assertCreated()
            ->assertJsonPath('comment.status', 'pending')
            ->assertJsonPath('comment.name', 'Pembaca')
            ->assertJsonMissingPath('comment.email')
            ->assertJsonMissingPath('comment.ip_hash')
            ->assertJsonMissingPath('comment.user_agent');

        $this->assertDatabaseHas('comments', [
            'post_id' => $post->id,
            'status' => 'pending',
            'name' => 'Pembaca',
        ]);
    }

    public function test_store_creates_approved_comment_when_moderation_disabled(): void
    {
        $post = Post::factory()->published()->create();
        Setting::setValue('comment_moderation', 'false');

        $this->postJson("/api/posts/{$post->id}/comments", [
            'name' => 'Pembaca',
            'email' => 'pembaca@example.com',
            'body' => 'Terima kasih solusinya!',
        ])->assertCreated()
            ->assertJsonPath('comment.status', 'approved');
    }

    public function test_store_rejects_spam_honeypot(): void
    {
        $post = Post::factory()->published()->create();

        $this->postJson("/api/posts/{$post->id}/comments", [
            'name' => 'Bot',
            'email' => 'bot@example.com',
            'body' => 'Spam body',
            'website' => 'http://spam.example.com',
        ])->assertUnprocessable();

        $this->assertDatabaseCount('comments', 0);
    }

    public function test_store_rejects_comment_with_too_many_links(): void
    {
        $post = Post::factory()->published()->create();

        $this->postJson("/api/posts/{$post->id}/comments", [
            'name' => 'Spammer',
            'email' => 'spam@example.com',
            'body' => 'Visit http://a.com and http://b.com and http://c.com now',
        ])->assertUnprocessable();

        $this->assertDatabaseCount('comments', 0);
    }

    public function test_store_requires_valid_fields(): void
    {
        $post = Post::factory()->published()->create();

        $this->postJson("/api/posts/{$post->id}/comments", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email', 'body']);
    }

    public function test_store_returns_indonesian_status_messages(): void
    {
        $post = Post::factory()->published()->create();

        Setting::setValue('comment_moderation', 'true');

        $this->postJson("/api/posts/{$post->id}/comments", [
            'name' => 'Pembaca',
            'email' => 'pembaca@example.com',
            'body' => 'Menunggu moderasi.',
        ])->assertCreated()
            ->assertJsonPath('message', 'Komentar berhasil dikirim dan menunggu persetujuan.');

        Setting::setValue('comment_moderation', 'false');

        $this->postJson("/api/posts/{$post->id}/comments", [
            'name' => 'Pembaca',
            'email' => 'pembaca@example.com',
            'body' => 'Langsung tampil.',
        ])->assertCreated()
            ->assertJsonPath('message', 'Komentar berhasil dikirim.');
    }

    public function test_store_returns_404_for_unpublished_post(): void
    {
        $post = Post::factory()->draft()->create();

        $this->postJson("/api/posts/{$post->id}/comments", [
            'name' => 'Pembaca',
            'email' => 'pembaca@example.com',
            'body' => 'Komentar',
        ])->assertNotFound();

        $this->assertDatabaseCount('comments', 0);
    }

    public function test_admin_can_comment_without_name_and_email(): void
    {
        $post = Post::factory()->published()->create();
        $admin = User::factory()->admin()->create(['name' => 'Admin']);
        Setting::setValue('author_name', 'Godang');
        Setting::setValue('comment_moderation', 'true');

        $response = $this->actingAs($admin)->postJson("/api/posts/{$post->id}/comments", [
            'body' => 'Catatan dari admin.',
        ]);

        $response->assertCreated()
            ->assertJsonPath('message', 'Komentar berhasil dikirim.')
            ->assertJsonPath('comment.name', 'Godang')
            ->assertJsonPath('comment.status', 'approved')
            ->assertJsonPath('comment.is_author', true);

        $this->assertDatabaseHas('comments', [
            'post_id' => $post->id,
            'name' => 'Godang',
            'email' => $admin->email,
            'status' => 'approved',
            'is_author' => true,
        ]);
    }

    public function test_admin_comment_uses_author_name_instead_of_submitted_fields(): void
    {
        $post = Post::factory()->published()->create();
        $admin = User::factory()->admin()->create(['name' => 'Admin']);
        Setting::setValue('author_name', 'Nama Penulis');

        $this->actingAs($admin)->postJson("/api/posts/{$post->id}/comments", [
            'name' => 'Nama Palsu',
            'email' => 'palsu@example.com',
            'body' => 'Halo.',
        ])->assertCreated();

        $this->assertDatabaseMissing('comments', ['email' => 'palsu@example.com']);
        $this->assertDatabaseHas('comments', [
            'post_id' => $post->id,
            'name' => 'Nama Penulis',
            'email' => $admin->email,
        ]);
    }

    public function test_author_comments_display_configured_author_name(): void
    {
        $post = Post::factory()->published()->create();
        $admin = User::factory()->admin()->create(['name' => 'Admin']);
        Setting::setValue('author_name', 'Nama Penulis');
        Comment::factory()->approved()->create([
            'post_id' => $post->id,
            'name' => 'Admin',
            'is_author' => true,
        ]);

        $this->get("/blog/{$post->slug}")
            ->assertOk()
            ->assertSee('Nama Penulis');

        $this->actingAs($admin)->getJson('/api/admin/comments')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Nama Penulis');
    }

    public function test_non_admin_user_still_requires_name_and_email(): void
    {
        $post = Post::factory()->published()->create();
        $user = User::factory()->create();

        $this->actingAs($user)->postJson("/api/posts/{$post->id}/comments", [
            'body' => 'Tanpa identitas.',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'email']);
    }

    public function test_blog_page_shows_admin_comment_controls_and_hides_identity_fields(): void
    {
        $post = Post::factory()->published()->create();
        $admin = User::factory()->admin()->create(['name' => 'Penulis Utama']);
        Comment::factory()->approved()->create(['post_id' => $post->id]);

        $this->actingAs($admin)->get("/blog/{$post->slug}")
            ->assertOk()
            ->assertSee('Mode Admin')
            ->assertSee('comment-admin-delete', false)
            ->assertDontSee('id="comment-name"', false)
            ->assertDontSee('name="email"', false);
    }

    public function test_blog_page_shows_edit_button_only_for_author_comments(): void
    {
        $post = Post::factory()->published()->create();
        $admin = User::factory()->admin()->create();

        Comment::factory()->approved()->create([
            'post_id' => $post->id,
            'is_author' => true,
        ]);
        Comment::factory()->approved()->create([
            'post_id' => $post->id,
            'is_author' => false,
        ]);

        $response = $this->actingAs($admin)->get("/blog/{$post->slug}")->assertOk();

        $this->assertSame(1, substr_count($response->getContent(), 'class="comment-admin-edit inline-flex'));
    }

    public function test_blog_page_hides_edit_button_for_guests(): void
    {
        $post = Post::factory()->published()->create();
        Comment::factory()->approved()->create([
            'post_id' => $post->id,
            'is_author' => true,
        ]);

        $response = $this->get("/blog/{$post->slug}")->assertOk();

        $this->assertSame(0, substr_count($response->getContent(), 'class="comment-admin-edit inline-flex'));
    }

    public function test_blog_page_hides_admin_comment_controls_for_guests(): void
    {
        $post = Post::factory()->published()->create();
        Comment::factory()->approved()->create(['post_id' => $post->id]);

        $this->get("/blog/{$post->slug}")
            ->assertOk()
            ->assertDontSee('Mode Admin')
            ->assertDontSee('comment-admin-delete', false)
            ->assertSee('id="comment-name"', false);
    }

    public function test_index_returns_only_approved_comments_with_hidden_private_fields(): void
    {
        $post = Post::factory()->published()->create();
        $approved = Comment::factory()->create(['post_id' => $post->id, 'status' => 'approved']);
        Comment::factory()->create(['post_id' => $post->id, 'status' => 'pending']);
        Comment::factory()->spam()->create(['post_id' => $post->id]);

        $response = $this->getJson("/api/posts/{$post->id}/comments");

        $response->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $approved->id)
            ->assertJsonPath('0.name', $approved->name)
            ->assertJsonMissingPath('0.email')
            ->assertJsonMissingPath('0.ip_hash')
            ->assertJsonMissingPath('0.user_agent');
    }

    public function test_index_returns_404_for_unpublished_post(): void
    {
        $post = Post::factory()->draft()->create();
        Comment::factory()->approved()->create(['post_id' => $post->id]);

        $this->getJson("/api/posts/{$post->id}/comments")->assertNotFound();
    }

    public function test_reply_to_a_reply_is_reparented_to_the_root_comment(): void
    {
        Setting::setValue('comment_moderation', 'false');
        $post = Post::factory()->published()->create();
        $root = Comment::factory()->approved()->create(['post_id' => $post->id]);
        $reply = Comment::factory()->approved()->create([
            'post_id' => $post->id,
            'parent_id' => $root->id,
        ]);

        $response = $this->postJson("/api/posts/{$post->id}/comments", [
            'name' => 'Pembaca',
            'email' => 'pembaca@example.com',
            'body' => 'Balasan untuk balasan.',
            'parent_id' => $reply->id,
        ])->assertCreated();

        $this->assertSame($root->id, $response->json('comment.parent_id'));
    }

    public function test_store_strips_html_from_the_comment_body(): void
    {
        Setting::setValue('comment_moderation', 'false');
        $post = Post::factory()->published()->create();

        $response = $this->postJson("/api/posts/{$post->id}/comments", [
            'name' => 'Pembaca',
            'email' => 'pembaca@example.com',
            'body' => '<p>Halo <strong>dunia</strong></p><script>alert("xss")</script>',
        ])->assertCreated();

        $this->assertSame('Halo dunia', $response->json('comment.body'));
        $this->assertDatabaseHas('comments', [
            'id' => $response->json('comment.id'),
            'body' => 'Halo dunia',
        ]);
    }

    public function test_store_is_rate_limited_after_six_requests(): void
    {
        Setting::setValue('comment_moderation', 'false');
        $post = Post::factory()->published()->create();
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.77']);

        for ($i = 1; $i <= 6; $i++) {
            $this->postJson("/api/posts/{$post->id}/comments", [
                'name' => 'Pembaca',
                'email' => 'pembaca@example.com',
                'body' => "Komentar ke-{$i}.",
            ])->assertCreated();
        }

        $this->postJson("/api/posts/{$post->id}/comments", [
            'name' => 'Pembaca',
            'email' => 'pembaca@example.com',
            'body' => 'Komentar ketujuh.',
        ])->assertStatus(429);
    }
}
