<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Post;
use Illuminate\Database\Seeder;

class CommentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $publishedPosts = Post::where('status', 'published')->take(6)->get();

        foreach ($publishedPosts as $post) {
            // Create 2-4 root comments per post
            $commentCount = rand(2, 4);

            for ($i = 0; $i < $commentCount; $i++) {
                $comment = Comment::factory()->create([
                    'post_id' => $post->id,
                ]);

                // 50% chance of an admin reply
                if (fake()->boolean(50)) {
                    Comment::factory()->authorReply()->create([
                        'post_id' => $post->id,
                        'parent_id' => $comment->id,
                    ]);
                }
            }

            // Add 1 pending comment
            Comment::factory()->pending()->create([
                'post_id' => $post->id,
            ]);
        }
    }
}
