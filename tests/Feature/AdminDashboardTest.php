<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_recent_comments_use_author_name_for_author_comments(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Admin']);
        Setting::setValue('author_name', 'Nama Penulis');

        Comment::factory()->authorReply()->create(['name' => 'Admin']);
        Comment::factory()->approved()->create(['name' => 'Pembaca']);

        $recent = collect(
            $this->actingAs($admin)
                ->getJson('/api/admin/dashboard')
                ->assertOk()
                ->json('recent_comments')
        );

        $this->assertSame('Nama Penulis', $recent->firstWhere('is_author', true)['name']);
        $this->assertSame('Pembaca', $recent->firstWhere('is_author', false)['name']);
    }
}
