<?php

namespace Database\Factories;

use App\Models\Media;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Media>
 */
class MediaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'path' => 'uploads/'.fake()->uuid().'.webp',
            'type' => 'image',
            'original_name' => null,
            'folder' => 'uploads',
            'alt_text' => fake()->sentence(5),
            'mime' => 'image/webp',
            'size' => fake()->numberBetween(50000, 500000),
            'width' => fake()->randomElement([800, 1200, 1600]),
            'height' => fake()->randomElement([450, 675, 900]),
        ];
    }

    /**
     * Indicate that the media is a non-image file.
     */
    public function file(): static
    {
        return $this->state(fn (array $attributes) => [
            'path' => 'uploads/'.fake()->uuid().'.pdf',
            'type' => 'file',
            'original_name' => 'dokumen.pdf',
            'mime' => 'application/pdf',
            'thumb_path' => null,
            'width' => null,
            'height' => null,
        ]);
    }
}
