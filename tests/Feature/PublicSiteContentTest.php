<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PublicSiteContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_about_page_renders_configured_profile_content(): void
    {
        Setting::setValue('author_name', 'Budi Santoso');
        Setting::setValue('author_avatar', 'avatar/budi.webp');
        Setting::setValue('author_avatar_alt', 'Foto Budi');
        Setting::setValue('author_role', 'Backend Engineer');
        Setting::setValue('about_bio', "Paragraf pertama.\n\nParagraf kedua.");
        Setting::setValue('about_topics_heading', 'Topik Favorit');
        Setting::setValue('about_topics', "Laravel\nPHP");
        Setting::setValue('about_contact_heading', 'Hubungi');
        Setting::setValue('about_contact', 'Kirim email ke saya.');
        Setting::setValue('about_meta_description', 'Profil Budi.');
        Setting::setValue('github_url', 'https://github.com/budi');

        $this->get('/about')
            ->assertOk()
            ->assertSee('Budi Santoso')
            ->assertSee('Backend Engineer')
            ->assertSee('Paragraf pertama.')
            ->assertSee('Paragraf kedua.')
            ->assertSee('Topik Favorit')
            ->assertSee('Laravel')
            ->assertSee('PHP')
            ->assertSee('Kirim email ke saya.')
            ->assertSee('Profil Budi.')
            ->assertSee('https://github.com/budi', false)
            ->assertSee(Storage::disk('public')->url('avatar/budi.webp'), false)
            ->assertSee('"@type":"Person"', false);
    }

    public function test_landing_and_blog_pages_render_indonesian_content(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Blog Teknis')
            ->assertSee('Masalah Teknis,')
            ->assertSee('Solusi Nyata.')
            ->assertSee('Catatan masalah teknis yang pernah saya hadapi beserta solusinya.')
            ->assertSee('Tentang Saya');

        $this->get('/blog')
            ->assertOk()
            ->assertSee('Kumpulan catatan masalah teknis beserta solusinya.')
            ->assertSee('Semua artikel tentang masalah teknis dan solusinya.');
    }

    public function test_feed_uses_configured_site_title_and_hardcoded_description(): void
    {
        Setting::setValue('site_title', 'Catatan Dev');

        $this->get('/feed.xml')
            ->assertOk()
            ->assertSee('Catatan Dev', false)
            ->assertSee('Kumpulan catatan masalah teknis beserta solusinya.', false);
    }

    public function test_empty_social_links_are_not_rendered(): void
    {
        Setting::setValue('github_url', null);
        Setting::setValue('twitter_url', null);
        Setting::setValue('linkedin_url', null);

        $this->get('/about')
            ->assertOk()
            ->assertDontSee('id="about-github"', false)
            ->assertDontSee('id="about-twitter"', false)
            ->assertDontSee('id="about-linkedin"', false)
            ->assertDontSee('id="social-github"', false);
    }

    public function test_public_pages_escape_admin_content(): void
    {
        Setting::setValue('author_name', '<script>alert(1)</script>');

        $this->get('/about')
            ->assertOk()
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false);
    }

    public function test_navbar_shows_admin_mode_link_for_administrators(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get('/')
            ->assertOk()
            ->assertSee('id="nav-admin-mode"', false)
            ->assertSee('Mode Admin')
            ->assertSee('href="'.url('/admin').'"', false);
    }

    public function test_navbar_hides_admin_mode_link_for_guests_and_regular_users(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertDontSee('nav-admin-mode', false)
            ->assertDontSee('Mode Admin');

        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)->get('/')
            ->assertOk()
            ->assertDontSee('nav-admin-mode', false)
            ->assertDontSee('Mode Admin');
    }

    public function test_navbar_renders_light_and_dark_logo_variants(): void
    {
        Setting::setValue('site_logo', 'logo/light.webp');
        Setting::setValue('site_logo_dark', 'logo/dark.webp');

        $this->get('/')
            ->assertOk()
            ->assertSee(Storage::disk('public')->url('logo/light.webp'), false)
            ->assertSee(Storage::disk('public')->url('logo/dark.webp'), false)
            ->assertSee('dark:hidden', false)
            ->assertSee('dark:block', false);

        $this->getJson('/api/site/settings')
            ->assertOk()
            ->assertJsonPath('site_logo', 'logo/light.webp')
            ->assertJsonPath('site_logo_dark', 'logo/dark.webp');
    }

    public function test_public_pages_fall_back_to_defaults_when_settings_are_empty(): void
    {
        $this->get('/about')
            ->assertOk()
            ->assertSee('Developer & Penulis Teknis')
            ->assertSee('Topik yang Dibahas');

        $this->get('/blog')
            ->assertOk()
            ->assertSee('Kumpulan catatan masalah teknis beserta solusinya.');
    }
}
