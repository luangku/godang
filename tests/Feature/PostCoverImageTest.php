<?php

namespace Tests\Feature;

use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PostCoverImageTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_detail_renders_absolute_cover_image_url(): void
    {
        Storage::fake('public');
        Post::factory()->indonesian()->published()->create([
            'slug' => 'cover-relatif',
            'cover_image' => 'cover/test.webp',
        ]);

        $response = $this->get('/blog/cover-relatif')->assertOk();
        $response->assertSee(Storage::disk('public')->url('cover/test.webp'), false);
    }

    public function test_blog_index_card_renders_absolute_cover_image_url(): void
    {
        Storage::fake('public');
        Post::factory()->indonesian()->published()->create([
            'slug' => 'card-cover',
            'cover_image' => 'cover/card.webp',
        ]);

        $response = $this->get('/blog')->assertOk();
        $response->assertSee(Storage::disk('public')->url('cover/card.webp'), false);
    }

    public function test_og_image_meta_tag_uses_absolute_url(): void
    {
        Storage::fake('public');
        Post::factory()->indonesian()->published()->create([
            'slug' => 'og-cover',
            'cover_image' => 'cover/og.webp',
        ]);

        $response = $this->get('/blog/og-cover')->assertOk();
        $response->assertSee('property="og:image"', false);
        $response->assertSee(Storage::disk('public')->url('cover/og.webp'), false);
    }

    public function test_external_cover_image_url_is_kept_unchanged(): void
    {
        Post::factory()->indonesian()->published()->create([
            'slug' => 'external-cover',
            'cover_image' => 'https://example.com/image.jpg',
        ]);

        $response = $this->get('/blog/external-cover')->assertOk();
        $response->assertSee('https://example.com/image.jpg', false);
    }

    public function test_legacy_absolute_storage_cover_url_is_normalized(): void
    {
        Storage::fake('public');
        Post::factory()->indonesian()->published()->create([
            'slug' => 'legacy-absolute-cover',
            'cover_image' => rtrim((string) config('app.url'), '/').'/storage/cover/legacy.webp',
        ]);

        $response = $this->get('/blog/legacy-absolute-cover')->assertOk();
        $response->assertSee(Storage::disk('public')->url('cover/legacy.webp'), false);
    }
}
