<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Post>
 */
class PostFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(rand(6, 12));

        return [
            'user_id' => User::factory(),
            'category_id' => Category::factory(),
            'title' => $title,
            'slug' => Str::slug($title),
            'excerpt' => fake()->paragraph(2),
            'content' => $this->generateContent(),
            'cover_image' => null,
            'status' => 'published',
            'published_at' => fake()->dateTimeBetween('-6 months', 'now'),
            'meta_title' => null,
            'meta_description' => fake()->sentence(15),
            'reading_time' => fake()->numberBetween(3, 15),
            'views_count' => fake()->numberBetween(0, 5000),
        ];
    }

    /**
     * Generate sample article content with headings and paragraphs.
     */
    private function generateContent(): string
    {
        $sections = [];
        $headingCount = fake()->numberBetween(2, 5);

        for ($i = 0; $i < $headingCount; $i++) {
            $sections[] = '<h2>'.fake()->sentence(rand(4, 8)).'</h2>';
            $sections[] = '<p>'.fake()->paragraphs(rand(2, 4), true).'</p>';

            if (fake()->boolean(40)) {
                $sections[] = '<pre><code>'.fake()->text(200).'</code></pre>';
            }
        }

        return implode("\n\n", $sections);
    }

    /**
     * State: published post.
     */
    public function published(): static
    {
        return $this->state(fn () => [
            'status' => 'published',
            'published_at' => fake()->dateTimeBetween('-6 months', 'now'),
        ]);
    }

    /**
     * State: draft post.
     */
    public function draft(): static
    {
        return $this->state(fn () => [
            'status' => 'draft',
            'published_at' => null,
        ]);
    }

    /**
     * State: scheduled post.
     */
    public function scheduled(): static
    {
        return $this->state(fn () => [
            'status' => 'scheduled',
            'published_at' => fake()->dateTimeBetween('+1 day', '+1 month'),
        ]);
    }

    /**
     * State: Indonesian content.
     */
    public function indonesian(): static
    {
        return $this;
    }
}
