<?php

namespace Tests\Feature;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPostsTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_clamps_per_page_between_1_and_50(): void
    {
        Post::factory()->count(2)->published()->create();

        $this->getJson('/api/posts?per_page=999')
            ->assertOk()
            ->assertJsonPath('per_page', 50);

        $this->getJson('/api/posts?per_page=0')
            ->assertOk()
            ->assertJsonPath('per_page', 1);
    }

    public function test_index_excludes_drafts_and_trashed_posts(): void
    {
        Post::factory()->published()->create();
        Post::factory()->draft()->create();
        $trashed = Post::factory()->published()->create();
        $trashed->delete();

        $this->getJson('/api/posts')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_show_returns_only_published_post_by_slug(): void
    {
        $post = Post::factory()->indonesian()->published()->create(['slug' => 'artikel-terbit']);
        Post::factory()->indonesian()->draft()->create(['slug' => 'artikel-draft']);

        $this->getJson('/api/posts/artikel-terbit')
            ->assertOk()
            ->assertJsonPath('slug', 'artikel-terbit');

        $this->getJson('/api/posts/artikel-draft')
            ->assertNotFound();
    }

    public function test_index_searches_post_content(): void
    {
        Post::factory()->indonesian()->published()->create([
            'title' => 'Artikel tentang Laravel',
            'excerpt' => 'Ringkasan biasa.',
            'content' => '<p>Pembahasan kata kunci unik: scheduler-queue.</p>',
        ]);

        $this->getJson('/api/posts?q=scheduler-queue')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Artikel tentang Laravel');
    }

    public function test_blog_page_searches_post_content(): void
    {
        Post::factory()->indonesian()->published()->create([
            'title' => 'Artikel tentang Laravel',
            'excerpt' => 'Ringkasan biasa.',
            'content' => '<p>Pembahasan kata kunci unik: scheduler-queue.</p>',
        ]);

        $this->get('/blog?q=scheduler-queue')
            ->assertOk()
            ->assertSee('Artikel tentang Laravel');
    }
}
