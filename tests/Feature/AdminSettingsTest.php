<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSettingsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->primaryAdmin()->create();
    }

    public function test_index_returns_all_settings_as_key_value_map(): void
    {
        $admin = $this->admin();
        Setting::setValue('site_title', 'Judul Situs');

        $this->actingAs($admin)
            ->getJson('/api/admin/settings')
            ->assertOk()
            ->assertJsonPath('site_title', 'Judul Situs');
    }

    public function test_update_persists_allowed_keys(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->putJson('/api/admin/settings', [
                'settings' => [
                    'site_title' => 'Baru',
                    'comment_moderation' => 'false',
                    'tagline' => 'Tagline baru',
                ],
            ])
            ->assertOk();

        $this->assertSame('Baru', Setting::getValue('site_title'));
        $this->assertSame('false', Setting::getValue('comment_moderation'));
        $this->assertSame('Tagline baru', Setting::getValue('tagline'));
    }

    public function test_update_rejects_unknown_keys(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->putJson('/api/admin/settings', [
                'settings' => ['evil_key' => 'x'],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['settings']);

        $this->assertNull(Setting::getValue('evil_key'));
    }

    public function test_update_rejects_english_keys(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->putJson('/api/admin/settings', [
                'settings' => [
                    'site_description_en' => 'English description',
                    'about_bio_en' => 'English bio',
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['settings']);

        $this->assertNull(Setting::getValue('site_description_en'));
        $this->assertNull(Setting::getValue('about_bio_en'));
    }

    public function test_update_persists_light_and_dark_logo_variants(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->putJson('/api/admin/settings', [
                'settings' => [
                    'site_logo' => 'logo/light.webp',
                    'site_logo_dark' => 'logo/dark.webp',
                ],
            ])
            ->assertOk();

        $this->assertSame('logo/light.webp', Setting::getValue('site_logo'));
        $this->assertSame('logo/dark.webp', Setting::getValue('site_logo_dark'));
    }

    public function test_update_requires_settings_array(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->putJson('/api/admin/settings', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['settings']);
    }

    public function test_update_allows_null_value_to_clear_a_setting(): void
    {
        $admin = $this->admin();
        Setting::setValue('github_url', 'https://github.com/old');

        $this->actingAs($admin)
            ->putJson('/api/admin/settings', [
                'settings' => ['github_url' => null],
            ])
            ->assertOk();

        $this->assertNull(Setting::getValue('github_url'));
    }

    public function test_index_returns_defaults_for_keys_not_stored_yet(): void
    {
        $admin = User::factory()->primaryAdmin()->create(['name' => 'Admin Utama']);

        $this->actingAs($admin)
            ->getJson('/api/admin/settings')
            ->assertOk()
            ->assertJsonPath('author_name', 'Admin Utama')
            ->assertJsonPath('about_topics_heading', 'Topik yang Dibahas');
    }

    public function test_update_persists_about_content_keys_without_changing_author_name(): void
    {
        $admin = $this->admin();
        Setting::setValue('author_name', 'Nama Lama');

        $this->actingAs($admin)
            ->putJson('/api/admin/settings', [
                'settings' => [
                    'author_name' => 'Penulis Baru',
                    'author_role' => 'Backend Engineer',
                    'about_bio' => 'Paragraf pertama.'.PHP_EOL.PHP_EOL.'Paragraf kedua.',
                    'about_topics' => "Laravel\nPHP",
                ],
            ])
            ->assertOk();

        $this->assertSame('Nama Lama', Setting::getValue('author_name'));
        $this->assertSame('Backend Engineer', Setting::getValue('author_role'));
        $this->assertSame("Laravel\nPHP", Setting::getValue('about_topics'));
    }

    public function test_update_rejects_legacy_avatar_settings(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->putJson('/api/admin/settings', [
                'settings' => [
                    'author_avatar' => 'avatar/foto.webp',
                    'author_avatar_alt' => 'Foto penulis',
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['settings']);

        $this->assertNull(Setting::getValue('author_avatar'));
        $this->assertNull(Setting::getValue('author_avatar_alt'));
    }

    public function test_index_derives_author_name_from_the_primary_admin(): void
    {
        $primary = User::factory()->primaryAdmin()->create(['name' => 'Admin Utama']);
        Setting::setValue('author_name', 'Nama Lama');

        $this->actingAs($primary)
            ->getJson('/api/admin/settings')
            ->assertOk()
            ->assertJsonPath('author_name', 'Admin Utama');
    }

    public function test_regular_admin_cannot_access_settings(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->getJson('/api/admin/settings')
            ->assertForbidden();

        $this->actingAs($admin)
            ->putJson('/api/admin/settings', [
                'settings' => ['site_title' => 'Tidak Boleh'],
            ])
            ->assertForbidden();

        $this->assertNull(Setting::getValue('site_title'));
    }

    public function test_update_ignores_author_name_when_a_primary_admin_exists(): void
    {
        $primary = User::factory()->primaryAdmin()->create(['name' => 'Admin Utama']);

        $this->actingAs($primary)
            ->putJson('/api/admin/settings', [
                'settings' => ['author_name' => 'Coba Ganti'],
            ])
            ->assertOk();

        $this->assertNull(Setting::getValue('author_name'));
        $this->assertSame('Admin Utama', Setting::authorName());
    }

    public function test_update_accepts_a_long_bio_but_rejects_values_over_the_limit(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->putJson('/api/admin/settings', [
                'settings' => ['about_bio' => str_repeat('a', 3000)],
            ])
            ->assertOk();

        $this->assertSame(3000, strlen((string) Setting::getValue('about_bio')));

        $this->actingAs($admin)
            ->putJson('/api/admin/settings', [
                'settings' => ['about_bio' => str_repeat('a', 5001)],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['settings.about_bio']);
    }

    public function test_update_rejects_invalid_social_urls(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->putJson('/api/admin/settings', [
                'settings' => ['github_url' => 'bukan-url'],
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['settings.github_url']);
    }

    public function test_settings_endpoints_require_admin(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/admin/settings')
            ->assertForbidden();

        $this->actingAs($user)
            ->putJson('/api/admin/settings', ['settings' => ['site_title' => 'x']])
            ->assertForbidden();
    }
}
