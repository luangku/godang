<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The landing page is served from the root URL.
     */
    public function test_landing_page_renders_at_root(): void
    {
        $this->get('/')->assertOk();
    }

    /**
     * The root URL no longer redirects based on the browser language.
     */
    public function test_root_ignores_accept_language_header(): void
    {
        $this->get('/', ['Accept-Language' => 'fr-FR'])->assertOk();
    }

    /**
     * Legacy locale-prefixed URLs redirect permanently to root equivalents.
     */
    public function test_legacy_locale_prefixes_redirect_permanently(): void
    {
        $this->get('/id')->assertRedirect('/')->assertStatus(301);
        $this->get('/id/blog')->assertRedirect('/blog')->assertStatus(301);
        $this->get('/en/about')->assertRedirect('/about')->assertStatus(301);
        $this->get('/en/blog/artikel-lama')->assertRedirect('/blog/artikel-lama')->assertStatus(301);
    }

    /**
     * Unknown locale prefixes still return 404.
     */
    public function test_unknown_locale_prefix_is_not_found(): void
    {
        $this->get('/fr/blog')->assertNotFound();
    }
}
