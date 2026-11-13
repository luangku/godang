<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthLoginTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Requests from the SPA origin must be stateful so the session cookie is issued.
     *
     * @return array<string, string>
     */
    private function statefulHeaders(): array
    {
        return ['Referer' => 'http://localhost'];
    }

    public function test_login_succeeds_for_admin_credentials(): void
    {
        $admin = User::factory()->admin()->create([
            'email' => 'admin@example.com',
        ]);

        $this->withHeaders($this->statefulHeaders())
            ->postJson('/api/auth/login', [
                'email' => 'admin@example.com',
                'password' => 'password',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Login berhasil.')
            ->assertJsonPath('user.email', 'admin@example.com');

        $this->assertAuthenticatedAs($admin);
    }

    public function test_login_fails_with_invalid_password(): void
    {
        User::factory()->admin()->create([
            'email' => 'admin@example.com',
        ]);

        $this->withHeaders($this->statefulHeaders())
            ->postJson('/api/auth/login', [
                'email' => 'admin@example.com',
                'password' => 'wrong-password',
            ])
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Email atau password salah.');

        $this->assertGuest();
    }

    public function test_login_fails_for_non_admin_user(): void
    {
        User::factory()->create([
            'email' => 'reader@example.com',
        ]);

        $this->withHeaders($this->statefulHeaders())
            ->postJson('/api/auth/login', [
                'email' => 'reader@example.com',
                'password' => 'password',
            ])
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Email atau password salah.');

        $this->assertGuest();
    }

    public function test_login_requires_email_and_password(): void
    {
        $this->withHeaders($this->statefulHeaders())
            ->postJson('/api/auth/login', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'password']);
    }

    public function test_login_is_throttled_after_five_failed_attempts(): void
    {
        User::factory()->admin()->create([
            'email' => 'admin@example.com',
        ]);

        foreach (range(1, 5) as $attempt) {
            $this->withHeaders($this->statefulHeaders())
                ->postJson('/api/auth/login', [
                    'email' => 'admin@example.com',
                    'password' => 'wrong-password',
                ])
                ->assertUnauthorized();
        }

        $this->withHeaders($this->statefulHeaders())
            ->postJson('/api/auth/login', [
                'email' => 'admin@example.com',
                'password' => 'wrong-password',
            ])
            ->assertTooManyRequests();
    }

    public function test_login_requires_turnstile_when_it_is_configured(): void
    {
        config(['services.turnstile.secret_key' => 'test-secret']);

        User::factory()->admin()->create([
            'email' => 'admin@example.com',
        ]);

        $this->withHeaders($this->statefulHeaders())
            ->postJson('/api/auth/login', [
                'email' => 'admin@example.com',
                'password' => 'password',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['cf_turnstile_response']);

        $this->assertGuest();
    }

    public function test_login_rejects_an_invalid_turnstile_token(): void
    {
        config(['services.turnstile.secret_key' => 'test-secret']);

        Http::fake([
            'challenges.cloudflare.com/*' => Http::response(['success' => false]),
        ]);

        User::factory()->admin()->create([
            'email' => 'admin@example.com',
        ]);

        $this->withHeaders($this->statefulHeaders())
            ->postJson('/api/auth/login', [
                'email' => 'admin@example.com',
                'password' => 'password',
                'cf_turnstile_response' => 'invalid-token',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['cf_turnstile_response']);

        $this->assertGuest();
    }

    public function test_login_succeeds_with_a_valid_turnstile_token(): void
    {
        config(['services.turnstile.secret_key' => 'test-secret']);

        Http::fake([
            'challenges.cloudflare.com/*' => Http::response(['success' => true]),
        ]);

        $admin = User::factory()->admin()->create([
            'email' => 'admin@example.com',
        ]);

        $this->withHeaders($this->statefulHeaders())
            ->postJson('/api/auth/login', [
                'email' => 'admin@example.com',
                'password' => 'password',
                'cf_turnstile_response' => 'valid-token',
            ])
            ->assertOk()
            ->assertJsonPath('user.email', 'admin@example.com');

        Http::assertSent(fn ($request) => $request['secret'] === 'test-secret'
            && $request['response'] === 'valid-token');

        $this->assertAuthenticatedAs($admin);
    }

    public function test_login_skips_turnstile_when_it_is_not_configured(): void
    {
        $admin = User::factory()->admin()->create([
            'email' => 'admin@example.com',
        ]);

        $this->withHeaders($this->statefulHeaders())
            ->postJson('/api/auth/login', [
                'email' => 'admin@example.com',
                'password' => 'password',
            ])
            ->assertOk();

        $this->assertAuthenticatedAs($admin);
    }

    public function test_logout_ends_the_session(): void
    {
        $admin = User::factory()->admin()->create();

        $this->withHeaders($this->statefulHeaders())
            ->actingAs($admin)
            ->postJson('/api/auth/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Berhasil keluar.');

        $this->assertGuest('web');
    }

    public function test_logout_requires_authentication(): void
    {
        $this->withHeaders($this->statefulHeaders())
            ->postJson('/api/auth/logout')
            ->assertUnauthorized();
    }

    public function test_me_returns_the_authenticated_user(): void
    {
        $admin = User::factory()->admin()->create();

        $this->withHeaders($this->statefulHeaders())
            ->actingAs($admin)
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('email', $admin->email)
            ->assertJsonMissingPath('password');
    }

    public function test_forgot_password_sends_a_link_to_the_reset_page(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create(['email' => 'admin@example.com']);

        $this->withHeaders($this->statefulHeaders())
            ->postJson('/api/auth/forgot-password', ['email' => 'admin@example.com'])
            ->assertOk();

        Notification::assertSentTo(
            $admin,
            ResetPassword::class,
            function (ResetPassword $notification) use ($admin): bool {
                $url = $notification->toMail($admin)->actionUrl;

                return str_contains($url, '/admin/reset-password')
                    && str_contains($url, 'token=')
                    && str_contains($url, 'email=');
            },
        );
    }

    public function test_reset_password_page_is_available(): void
    {
        $this->get('/admin/reset-password?token=test-token&email=admin%40example.com')
            ->assertOk()
            ->assertSee('admin-root', false);
    }

    public function test_reset_password_updates_the_admin_password(): void
    {
        $admin = User::factory()->admin()->create([
            'email' => 'admin@example.com',
            'password' => 'old-password',
        ]);
        $token = Password::createToken($admin);

        $this->withHeaders($this->statefulHeaders())
            ->postJson('/api/auth/reset-password', [
                'token' => $token,
                'email' => 'admin@example.com',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Kata sandi Anda sudah direset.');

        $this->assertTrue(Hash::check('new-password', $admin->fresh()->password));

        $this->withHeaders($this->statefulHeaders())
            ->postJson('/api/auth/login', [
                'email' => 'admin@example.com',
                'password' => 'new-password',
            ])
            ->assertOk();
    }

    public function test_reset_password_rejects_an_invalid_token(): void
    {
        User::factory()->admin()->create(['email' => 'admin@example.com']);

        $this->withHeaders($this->statefulHeaders())
            ->postJson('/api/auth/reset-password', [
                'token' => 'invalid-token',
                'email' => 'admin@example.com',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }
}
