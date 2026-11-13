<?php

namespace App\Console\Commands;

use App\Models\Post;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('posts:publish-scheduled')]
#[Description('Publish scheduled posts whose publication time has passed')]
class PublishScheduledPosts extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $count = Post::query()
            ->where('status', 'scheduled')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->update(['status' => 'published']);

        $this->info("{$count} scheduled post(s) published.");

        return self::SUCCESS;
    }
}
