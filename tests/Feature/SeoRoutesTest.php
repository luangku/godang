<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_lists_static_and_post_urls(): void
    {
        Post::factory()->indonesian()->published()->create(['slug' => 'artikel-terbit']);
        Post::factory()->indonesian()->draft()->create(['slug' => 'artikel-draft']);
        Category::create(['name' => 'Laravel', 'slug' => 'laravel']);
        Tag::create(['name' => 'PHP', 'slug' => 'php']);

        $response = $this->get('/sitemap.xml')->assertOk();
        $response->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

        $xml = $response->getContent();

        $this->assertStringContainsString(url('blog/artikel-terbit'), $xml);
        $this->assertStringContainsString(url('blog'), $xml);
        $this->assertStringContainsString(url('category/laravel'), $xml);
        $this->assertStringContainsString(url('tag/php'), $xml);
        $this->assertStringNotContainsString('artikel-draft', $xml);
    }

    public function test_sitemap_contains_only_root_urls(): void
    {
        Post::factory()->indonesian()->published()->create(['slug' => 'artikel-root']);
        Category::create(['name' => 'Laravel', 'slug' => 'laravel']);
        Tag::create(['name' => 'PHP', 'slug' => 'php']);

        $xml = $this->get('/sitemap.xml')->assertOk()->getContent();

        $this->assertStringNotContainsString(url('id/'), $xml);
        $this->assertStringNotContainsString(url('en/'), $xml);
        $this->assertStringContainsString(url('blog/artikel-root'), $xml);
    }

    public function test_sitemap_is_well_formed_xml(): void
    {
        Post::factory()->count(2)->published()->create();

        $response = $this->get('/sitemap.xml')->assertOk();

        $xml = simplexml_load_string($response->getContent());

        $this->assertNotFalse($xml);
        $this->assertSame('urlset', $xml->getName());
    }

    public function test_robots_txt_disallows_admin_and_api_and_points_to_sitemap(): void
    {
        $response = $this->get('/robots.txt')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8');

        $response->assertSee('User-agent: *');
        $response->assertSee('Disallow: /api/');
        $response->assertSee('Disallow: /admin');
        $response->assertSee('Sitemap: '.url('/sitemap.xml'));
    }

    public function test_feed_returns_published_posts(): void
    {
        Post::factory()->indonesian()->published()->create([
            'slug' => 'artikel-feed',
            'title' => 'Judul Feed',
            'excerpt' => 'Ringkasan artikel feed.',
        ]);
        Post::factory()->indonesian()->draft()->create(['slug' => 'draft-post']);

        $response = $this->get('/feed.xml')->assertOk();
        $response->assertHeader('Content-Type', 'application/rss+xml; charset=UTF-8');

        $xml = simplexml_load_string($response->getContent());

        $this->assertNotFalse($xml);
        $this->assertSame('rss', $xml->getName());
        $this->assertSame('Judul Feed', $xml->channel->item->title->__toString());
        $this->assertStringNotContainsString('draft-post', $response->getContent());
    }

    public function test_feed_is_limited_to_20_latest_posts(): void
    {
        Post::factory()->count(25)->published()->create();

        $xml = simplexml_load_string($this->get('/feed.xml')->assertOk()->getContent());

        $this->assertCount(20, $xml->channel->item);
    }

    public function test_feed_for_unknown_locale_is_not_found(): void
    {
        $this->get('/fr/feed.xml')->assertNotFound();
    }

    public function test_article_page_has_canonical_and_hreflang(): void
    {
        Post::factory()->indonesian()->published()->create(['slug' => 'artikel-seo']);

        $this->get('/blog/artikel-seo')
            ->assertOk()
            ->assertSee('<link rel="canonical" href="'.url('/blog/artikel-seo').'">', false)
            ->assertSee('<link rel="alternate" hreflang="id" href="'.url('/blog/artikel-seo').'">', false)
            ->assertSee('<link rel="alternate" hreflang="x-default" href="'.url('/blog/artikel-seo').'">', false);
    }

    public function test_unknown_locale_prefix_is_not_found(): void
    {
        $this->get('/fr/blog')->assertNotFound();
    }

    public function test_not_found_page_is_noindex_nofollow(): void
    {
        $this->get('/halaman-tidak-ada')
            ->assertNotFound()
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }
}
