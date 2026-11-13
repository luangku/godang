<?php

namespace App\Notifications;

use App\Models\Comment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewCommentNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public Comment $comment)
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
        $post = $this->comment->post;

        return (new MailMessage)
            ->subject('Komentar baru pada "'.$post->title.'"')
            ->greeting('Halo '.$notifiable->name.',')
            ->line($this->comment->name.' mengirim komentar baru:')
            ->line('"'.mb_strimwidth($this->comment->body, 0, 200, '…').'"')
            ->action('Moderasi komentar', url('/admin/comments'))
            ->line('Komentar ini menunggu persetujuan Anda.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'comment_id' => $this->comment->id,
            'post_id' => $this->comment->post_id,
        ];
    }
}
