<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_recent_comments_use_stored_names(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Admin Utama']);
        Setting::setValue('author_name', 'Nama Penulis');

        Comment::factory()->authorReply()->create(['name' => 'Admin Utama']);
        Comment::factory()->approved()->create(['name' => 'Pembaca']);

        $recent = collect(
            $this->actingAs($admin)
                ->getJson('/api/admin/dashboard')
                ->assertOk()
                ->json('recent_comments')
        );

        $this->assertSame('Admin Utama', $recent->firstWhere('is_author', true)['name']);
        $this->assertSame('Pembaca', $recent->firstWhere('is_author', false)['name']);
    }

    public function test_published_count_matches_the_public_visibility_scope(): void
    {
        $admin = User::factory()->admin()->create();

        Post::factory()->published()->create();
        Post::factory()->draft()->create();
        Post::factory()->scheduled()->create();
        Post::factory()->create([
            'status' => 'published',
            'published_at' => now()->addWeek(),
        ]);

        $this->actingAs($admin)
            ->getJson('/api/admin/dashboard')
            ->assertOk()
            ->assertJsonPath('published_posts', 1)
            ->assertJsonPath('draft_posts', 1)
            ->assertJsonPath('scheduled_posts', 1);
    }
}
