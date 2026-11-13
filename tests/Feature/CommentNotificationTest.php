<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\NewCommentNotification;
use App\Notifications\ReplyToCommentNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class CommentNotificationTest extends TestCase
{
    use RefreshDatabase;

    private function publishedPost(): Post
    {
        return Post::factory()->indonesian()->published()->create();
    }

    public function test_new_comment_notifies_admin_users(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        User::factory()->create();
        $post = $this->publishedPost();

        $this->postJson("/api/posts/{$post->id}/comments", [
            'name' => 'Penulis Komentar',
            'email' => 'komentator@example.com',
            'body' => 'Artikel yang sangat membantu, terima kasih!',
        ])->assertCreated();

        Notification::assertSentTo($admin, NewCommentNotification::class);
    }

    public function test_new_comment_does_not_notify_non_admin_users(): void
    {
        Notification::fake();
        $regular = User::factory()->create();
        User::factory()->admin()->create();
        $post = $this->publishedPost();

        $this->postJson("/api/posts/{$post->id}/comments", [
            'name' => 'Penulis Komentar',
            'email' => 'komentator@example.com',
            'body' => 'Terima kasih.',
        ])->assertCreated();

        Notification::assertNotSentTo($regular, NewCommentNotification::class);
    }

    public function test_notification_disabled_by_setting_skips_email(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        Setting::setValue('comment_notification', 'false');
        $post = $this->publishedPost();

        $this->postJson("/api/posts/{$post->id}/comments", [
            'name' => 'Penulis Komentar',
            'email' => 'komentator@example.com',
            'body' => 'Terima kasih.',
        ])->assertCreated();

        Notification::assertNothingSent();
    }

    public function test_notification_carries_comment_context(): void
    {
        Notification::fake();
        $admin = User::factory()->admin()->create();
        $post = $this->publishedPost();

        $this->postJson("/api/posts/{$post->id}/comments", [
            'name' => 'Budi',
            'email' => 'budi@example.com',
            'body' => 'Mantap.',
        ])->assertCreated();

        Notification::assertSentTo($admin, NewCommentNotification::class,
            function (NewCommentNotification $notification) use ($post) {
                return $notification->comment->post_id === $post->id
                    && $notification->comment->name === 'Budi';
            });
    }

    public function test_reply_notifies_parent_comment_author_when_replies_are_enabled(): void
    {
        Notification::fake();
        $post = $this->publishedPost();
        $parent = Comment::factory()->approved()->create([
            'post_id' => $post->id,
            'email' => 'pembaca@example.com',
            'notify_replies' => true,
        ]);

        $this->postJson("/api/posts/{$post->id}/comments", [
            'name' => 'Penulis',
            'email' => 'penulis@example.com',
            'body' => 'Terima kasih atas pertanyaannya.',
            'parent_id' => $parent->id,
        ])->assertCreated();

        Notification::assertSentOnDemand(
            ReplyToCommentNotification::class,
            function (ReplyToCommentNotification $notification, array $channels, AnonymousNotifiable $notifiable) use ($parent) {
                return $notifiable->routes['mail'] === 'pembaca@example.com'
                    && $notification->reply->parent_id === $parent->id;
            }
        );
    }

    public function test_reply_does_not_notify_parent_comment_author_when_replies_are_disabled(): void
    {
        Notification::fake();
        $post = $this->publishedPost();
        $parent = Comment::factory()->approved()->create([
            'post_id' => $post->id,
            'email' => 'pembaca@example.com',
            'notify_replies' => false,
        ]);

        $this->postJson("/api/posts/{$post->id}/comments", [
            'name' => 'Penulis',
            'email' => 'penulis@example.com',
            'body' => 'Terima kasih atas pertanyaannya.',
            'parent_id' => $parent->id,
        ])->assertCreated();

        Notification::assertSentOnDemandTimes(ReplyToCommentNotification::class, 0);
    }

    public function test_notification_failure_does_not_break_comment_submission(): void
    {
        Notification::shouldReceive('send')
            ->once()
            ->andThrow(new \RuntimeException('SMTP down'));

        User::factory()->admin()->create();
        $post = $this->publishedPost();

        $this->postJson("/api/posts/{$post->id}/comments", [
            'name' => 'Pembaca',
            'email' => 'pembaca@example.com',
            'body' => 'Komentar tetap terkirim.',
        ])->assertCreated();

        $this->assertDatabaseCount('comments', 1);
    }
}
