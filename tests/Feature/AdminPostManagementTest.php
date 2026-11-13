<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPostManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_store_rejects_duplicate_slug(): void
    {
        $admin = $this->admin();
        Post::factory()->indonesian()->create(['slug' => 'artikel-satu']);

        $this->actingAs($admin)
            ->postJson('/api/admin/posts', [
                'title' => 'Judul Lain',
                'slug' => 'artikel-satu',
                'status' => 'draft',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['slug']);
    }

    public function test_store_generates_unique_slug_from_title_when_slug_missing(): void
    {
        $admin = $this->admin();
        Post::factory()->indonesian()->create([
            'title' => 'Panduan Laravel',
            'slug' => 'panduan-laravel',
        ]);

        $this->actingAs($admin)
            ->postJson('/api/admin/posts', [
                'title' => 'Panduan Laravel',
                'status' => 'draft',
            ])
            ->assertCreated()
            ->assertJsonPath('slug', 'panduan-laravel-2');
    }

    public function test_update_ignores_empty_slug(): void
    {
        $admin = $this->admin();
        $post = Post::factory()->indonesian()->create(['slug' => 'slug-asli']);

        $this->actingAs($admin)
            ->patchJson("/api/admin/posts/{$post->id}", [
                'title' => 'Judul Baru',
                'slug' => '',
            ])
            ->assertOk()
            ->assertJsonPath('slug', 'slug-asli');

        $this->assertDatabaseHas('posts', ['id' => $post->id, 'slug' => 'slug-asli']);
    }

    public function test_update_rejects_duplicate_slug_of_another_post(): void
    {
        $admin = $this->admin();
        Post::factory()->indonesian()->create(['slug' => 'slug-terpakai']);
        $post = Post::factory()->indonesian()->create(['slug' => 'slug-sendiri']);

        $this->actingAs($admin)
            ->patchJson("/api/admin/posts/{$post->id}", ['slug' => 'slug-terpakai'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['slug']);
    }

    public function test_update_allows_keeping_its_own_slug(): void
    {
        $admin = $this->admin();
        $post = Post::factory()->indonesian()->create(['slug' => 'slug-sendiri']);

        $this->actingAs($admin)
            ->patchJson("/api/admin/posts/{$post->id}", [
                'title' => 'Judul Diubah',
                'slug' => 'slug-sendiri',
            ])
            ->assertOk()
            ->assertJsonPath('slug', 'slug-sendiri');
    }

    public function test_store_validates_og_image_type(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->postJson('/api/admin/posts', [
                'title' => 'Artikel OG',
                'status' => 'draft',
                'og_image' => ['not-a-string'],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['og_image']);
    }

    public function test_store_and_update_persist_og_image(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)
            ->postJson('/api/admin/posts', [
                'title' => 'Artikel OG',
                'status' => 'draft',
                'og_image' => 'uploads/og-custom.png',
            ])
            ->assertCreated()
            ->assertJsonPath('og_image', 'uploads/og-custom.png');

        $id = $response->json('id');

        $this->actingAs($admin)
            ->patchJson("/api/admin/posts/{$id}", ['og_image' => 'uploads/og-baru.png'])
            ->assertOk()
            ->assertJsonPath('og_image', 'uploads/og-baru.png');
    }

    public function test_index_clamps_per_page_between_1_and_50(): void
    {
        $admin = $this->admin();
        Post::factory()->count(2)->create();

        $this->actingAs($admin)
            ->getJson('/api/admin/posts?per_page=999')
            ->assertOk()
            ->assertJsonPath('per_page', 50);

        $this->actingAs($admin)
            ->getJson('/api/admin/posts?per_page=0')
            ->assertOk()
            ->assertJsonPath('per_page', 1);
    }

    public function test_index_excludes_trashed_posts_by_default(): void
    {
        $admin = $this->admin();
        $post = Post::factory()->published()->create();
        $post->delete();

        $this->actingAs($admin)
            ->getJson('/api/admin/posts')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_index_returns_only_trashed_posts_with_trashed_flag(): void
    {
        $admin = $this->admin();
        $trashed = Post::factory()->published()->create();
        $trashed->delete();
        $active = Post::factory()->published()->create();

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/posts?trashed=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $trashed->id);

        $this->assertNotContains($active->id, array_column($response->json('data'), 'id'));
    }

    public function test_bulk_delete_soft_deletes_posts(): void
    {
        $admin = $this->admin();
        $first = Post::factory()->published()->create();
        $second = Post::factory()->draft()->create();

        $this->actingAs($admin)
            ->postJson('/api/admin/posts/bulk-delete', [
                'ids' => [$first->id, $second->id],
            ])
            ->assertOk();

        $this->assertSoftDeleted('posts', ['id' => $first->id]);
        $this->assertSoftDeleted('posts', ['id' => $second->id]);
    }

    public function test_bulk_delete_validates_ids(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->postJson('/api/admin/posts/bulk-delete', ['ids' => [999]])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['ids.0']);
    }

    public function test_destroy_soft_deletes_by_default(): void
    {
        $admin = $this->admin();
        $post = Post::factory()->published()->create();

        $this->actingAs($admin)
            ->deleteJson("/api/admin/posts/{$post->id}")
            ->assertOk();

        $this->assertSoftDeleted('posts', ['id' => $post->id]);
    }

    public function test_destroy_force_permanently_deletes_post_and_comments(): void
    {
        $admin = $this->admin();
        $post = Post::factory()->published()->create();
        $comment = Comment::factory()->create(['post_id' => $post->id]);
        $post->delete();

        $this->actingAs($admin)
            ->deleteJson("/api/admin/posts/{$post->id}?force=1")
            ->assertOk()
            ->assertJsonPath('message', 'Artikel dihapus permanen.');

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    public function test_destroy_force_permanently_deletes_active_post(): void
    {
        $admin = $this->admin();
        $post = Post::factory()->published()->create();

        $this->actingAs($admin)
            ->deleteJson("/api/admin/posts/{$post->id}?force=1")
            ->assertOk();

        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }

    public function test_bulk_delete_force_permanently_deletes_posts(): void
    {
        $admin = $this->admin();
        $first = Post::factory()->published()->create();
        $second = Post::factory()->draft()->create();
        $second->delete();

        $this->actingAs($admin)
            ->postJson('/api/admin/posts/bulk-delete', [
                'ids' => [$first->id, $second->id],
                'force' => true,
            ])
            ->assertOk();

        $this->assertDatabaseMissing('posts', ['id' => $first->id]);
        $this->assertDatabaseMissing('posts', ['id' => $second->id]);
    }

    public function test_force_delete_requires_admin(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->published()->create();
        $post->delete();

        $this->actingAs($user)
            ->deleteJson("/api/admin/posts/{$post->id}?force=1")
            ->assertForbidden();

        $this->assertSoftDeleted('posts', ['id' => $post->id]);
    }

    public function test_restore_brings_back_soft_deleted_post(): void
    {
        $admin = $this->admin();
        $post = Post::factory()->published()->create();
        $post->delete();

        $this->actingAs($admin)
            ->postJson("/api/admin/posts/{$post->id}/restore")
            ->assertOk()
            ->assertJsonPath('post.id', $post->id);

        $this->assertNotSoftDeleted('posts', ['id' => $post->id]);
    }

    public function test_index_filters_posts_by_title_search(): void
    {
        $admin = $this->admin();
        Post::factory()->indonesian()->create(['title' => 'Panduan Laravel Dasar']);
        Post::factory()->indonesian()->create(['title' => 'Tips Deployment']);

        $this->actingAs($admin)
            ->getJson('/api/admin/posts?q=Laravel')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Panduan Laravel Dasar');
    }

    public function test_store_creates_new_tags_and_reuses_existing_ones(): void
    {
        $admin = $this->admin();
        $existing = Tag::create(['name' => 'Laravel', 'slug' => 'laravel']);

        $response = $this->actingAs($admin)
            ->postJson('/api/admin/posts', [
                'title' => 'Artikel Bertag',
                'status' => 'draft',
                'tags' => [$existing->id, 'PHP 8'],
            ])
            ->assertCreated();

        $post = Post::findOrFail($response->json('id'));

        $this->assertSame(['laravel', 'php-8'], $post->tags()->orderBy('slug')->pluck('slug')->all());
        $this->assertDatabaseHas('tags', ['name' => 'PHP 8', 'slug' => 'php-8']);
    }

    public function test_reading_time_is_calculated_from_article_content(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)
            ->postJson('/api/admin/posts', [
                'title' => 'Artikel Panjang',
                'content' => '<p>'.str_repeat('kata ', 401).'</p>',
                'status' => 'draft',
            ])
            ->assertCreated();

        $this->assertSame(3, $response->json('reading_time'));
    }

    public function test_reading_time_is_at_least_one_minute(): void
    {
        $admin = $this->admin();

        $response = $this->actingAs($admin)
            ->postJson('/api/admin/posts', [
                'title' => 'Artikel Pendek',
                'content' => '<p>Hanya beberapa kata.</p>',
                'status' => 'draft',
            ])
            ->assertCreated();

        $this->assertSame(1, $response->json('reading_time'));
    }

    public function test_restore_requires_admin(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->published()->create();
        $post->delete();

        $this->actingAs($user)
            ->postJson("/api/admin/posts/{$post->id}/restore")
            ->assertForbidden();
    }
}
