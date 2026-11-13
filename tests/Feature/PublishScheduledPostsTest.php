<?php

namespace Tests\Feature;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublishScheduledPostsTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_publishes_scheduled_posts_whose_time_has_passed(): void
    {
        $due = Post::factory()->create([
            'status' => 'scheduled',
            'published_at' => now()->subMinute(),
        ]);
        $notYet = Post::factory()->create([
            'status' => 'scheduled',
            'published_at' => now()->addDay(),
        ]);

        $this->artisan('posts:publish-scheduled')
            ->expectsOutputToContain('1 scheduled post(s) published.')
            ->assertSuccessful();

        $this->assertSame('published', $due->fresh()->status);
        $this->assertSame('scheduled', $notYet->fresh()->status);
    }

    public function test_command_ignores_posts_in_other_statuses(): void
    {
        $draft = Post::factory()->draft()->create();
        $published = Post::factory()->published()->create();

        $this->artisan('posts:publish-scheduled')
            ->expectsOutputToContain('0 scheduled post(s) published.')
            ->assertSuccessful();

        $this->assertSame('draft', $draft->fresh()->status);
        $this->assertSame('published', $published->fresh()->status);
    }

    public function test_scheduler_registers_the_command_every_minute(): void
    {
        $this->artisan('schedule:list')
            ->expectsOutputToContain('posts:publish-scheduled')
            ->assertSuccessful();
    }
}
