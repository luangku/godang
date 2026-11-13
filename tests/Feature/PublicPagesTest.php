<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_blog_index_has_search_and_category_filter_form(): void
    {
        Post::factory()->published()->create();

        $response = $this->get('/blog');

        $response->assertOk()
            ->assertSee('<form', false)
            ->assertSee('name="q"', false)
            ->assertSee('name="category"', false)
            ->assertSee('method="GET"', false);
    }

    public function test_blog_index_keeps_active_tag_filter_in_form(): void
    {
        $response = $this->get('/blog?tag=laravel');

        $response->assertOk()
            ->assertSee('name="tag"', false)
            ->assertSee('value="laravel"', false);
    }

    public function test_blog_index_filters_by_search_query(): void
    {
        Post::factory()->indonesian()->published()->create(['title' => 'Debugging Nginx 502']);
        Post::factory()->indonesian()->published()->create(['title' => 'Solusi Laravel Queue']);

        $this->get('/blog?q=Nginx')
            ->assertOk()
            ->assertSee('Debugging Nginx 502')
            ->assertDontSee('Solusi Laravel Queue');
    }

    public function test_post_detail_submits_comments_via_fetch_with_status_message(): void
    {
        $post = Post::factory()->indonesian()->published()->create();

        $this->get('/blog/'.$post->slug)
            ->assertOk()
            ->assertSee('id="comment-form"', false)
            ->assertSee('id="comment-status"', false)
            ->assertSee('fetch(form.action', false)
            ->assertSee('menunggu persetujuan', false);
    }

    public function test_post_detail_hides_reply_notification_checkbox_but_keeps_notifications_enabled(): void
    {
        $post = Post::factory()->indonesian()->published()->create();

        $this->get('/blog/'.$post->slug)
            ->assertOk()
            ->assertDontSee('Kirim notifikasi jika ada balasan')
            ->assertSee('<input type="hidden" name="notify_replies" value="1">', false);
    }

    public function test_post_detail_highlights_code_blocks_with_copy_button_support(): void
    {
        $post = Post::factory()->indonesian()->published()->create([
            'content' => '<pre><code class="language-php">echo 1;</code></pre>',
        ]);

        $this->get('/blog/'.$post->slug)
            ->assertOk()
            ->assertSee('id="article-content"', false)
            ->assertSee('<pre><code class="language-php">', false);
    }

    public function test_about_page_does_not_render_locale_hreflang(): void
    {
        $this->get('/about')
            ->assertOk()
            ->assertDontSee('hreflang="en"', false)
            ->assertSee(url('/about'), false);
    }

    public function test_public_pages_have_no_language_switcher(): void
    {
        foreach (['/', '/blog', '/about'] as $url) {
            $this->get($url)
                ->assertOk()
                ->assertDontSee('hreflang="en"', false)
                ->assertDontSee('href="/en', false)
                ->assertDontSee('href="/id', false);
        }
    }

    public function test_post_detail_has_no_translation_link(): void
    {
        $post = Post::factory()->indonesian()->published()->create();

        $this->get('/blog/'.$post->slug)
            ->assertOk()
            ->assertDontSee('hreflang="en"', false)
            ->assertDontSee('/en/blog', false);
    }

    public function test_post_detail_has_self_referential_hreflang(): void
    {
        $post = Post::factory()->indonesian()->published()->create();
        $url = url('/blog/'.$post->slug);

        $this->get('/blog/'.$post->slug)
            ->assertOk()
            ->assertSee('<link rel="alternate" hreflang="id" href="'.$url.'">', false)
            ->assertSee('<link rel="alternate" hreflang="x-default" href="'.$url.'">', false);
    }

    public function test_blog_index_filters_by_category_slug(): void
    {
        $category = Category::factory()->create(['slug' => 'devops']);
        Post::factory()->indonesian()->published()->create(['category_id' => $category->id]);
        Post::factory()->indonesian()->published()->create();

        $this->get('/blog?category=devops')
            ->assertOk()
            ->assertSee('selected', false);
    }

    public function test_category_page_renders_description(): void
    {
        $category = Category::factory()->create([
            'slug' => 'laravel',
            'description' => 'Framework PHP untuk aplikasi web modern.',
        ]);
        Post::factory()->indonesian()->published()->create(['category_id' => $category->id]);

        $this->get('/category/laravel')
            ->assertOk()
            ->assertSee('Framework PHP untuk aplikasi web modern.');
    }

    public function test_not_found_page_links_home_and_blog_to_root(): void
    {
        $this->get('/blog/artikel-tidak-ada')
            ->assertNotFound()
            ->assertSee('href="'.url('/').'"', false)
            ->assertSee('href="'.url('/blog').'"', false);
    }
}
