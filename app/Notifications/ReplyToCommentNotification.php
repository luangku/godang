<?php

namespace App\Notifications;

use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReplyToCommentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Comment $reply)
    {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $post = $this->reply->post;
        $postUrl = url('/blog/'.($post?->slug ?? '#'));
        $commentUrl = $postUrl.'#comment-'.$this->reply->id;

        return (new MailMessage)
            ->subject('Ada balasan untuk komentar Anda - '.$post?->title)
            ->greeting('Halo '.$notifiable->name.',')
            ->line($this->reply->name.' membalas komentar Anda:')
            ->line('"'.mb_strimwidth(strip_tags($this->reply->body), 0, 200, '...').'"')
            ->action('Lihat balasan', $commentUrl)
            ->line('Terima kasih telah berpartisipasi dalam diskusi.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'reply_id' => $this->reply->id,
            'post_id' => $this->reply->post_id,
            'parent_id' => $this->reply->parent_id,
        ];
    }
}
