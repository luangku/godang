<?php

namespace Database\Factories;

use App\Models\Comment;
use App\Models\Post;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Comment>
 */
class CommentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'post_id' => Post::factory(),
            'parent_id' => null,
            'name' => fake()->name(),
            'email' => fake()->safeEmail(),
            'body' => fake()->paragraph(rand(1, 3)),
            'status' => 'approved',
            'is_author' => false,
            'ip_hash' => hash('sha256', fake()->ipv4()),
            'user_agent' => fake()->userAgent(),
        ];
    }

    /**
     * State: approved.
     */
    public function approved(): static
    {
        return $this->state(fn () => ['status' => 'approved']);
    }

    /**
     * State: pending moderation.
     */
    public function pending(): static
    {
        return $this->state(fn () => ['status' => 'pending']);
    }

    /**
     * State: marked as spam.
     */
    public function spam(): static
    {
        return $this->state(fn () => ['status' => 'spam']);
    }

    /**
     * State: admin/author reply.
     */
    public function authorReply(): static
    {
        return $this->state(fn () => [
            'is_author' => true,
            'name' => 'Admin',
        ]);
    }
}
