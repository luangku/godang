<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AdminAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Endpoints every administrator may access.
     *
     * @return array<string, array{string}>
     */
    public static function adminEndpoints(): array
    {
        return [
            'dashboard' => ['/api/admin/dashboard'],
            'posts index' => ['/api/admin/posts'],
            'categories index' => ['/api/admin/categories'],
            'tags index' => ['/api/admin/tags'],
            'comments index' => ['/api/admin/comments'],
        ];
    }

    /**
     * Endpoints reserved for the primary administrator.
     *
     * @return array<string, array{string}>
     */
    public static function primaryAdminEndpoints(): array
    {
        return [
            'users index' => ['/api/admin/users'],
            'settings index' => ['/api/admin/settings'],
        ];
    }

    /**
     * Every admin endpoint, regardless of the required role.
     *
     * @return array<string, array{string}>
     */
    public static function allAdminEndpoints(): array
    {
        return [...self::adminEndpoints(), ...self::primaryAdminEndpoints()];
    }

    #[DataProvider('allAdminEndpoints')]
    public function test_admin_endpoints_require_authentication(string $uri): void
    {
        $this->getJson($uri)->assertUnauthorized();
    }

    #[DataProvider('allAdminEndpoints')]
    public function test_admin_endpoints_forbid_authenticated_non_admin_users(string $uri): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->getJson($uri)->assertForbidden();
    }

    #[DataProvider('adminEndpoints')]
    public function test_admin_endpoints_allow_admin_users(string $uri): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->getJson($uri)->assertOk();
    }

    #[DataProvider('primaryAdminEndpoints')]
    public function test_primary_admin_endpoints_forbid_regular_admin_users(string $uri): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->getJson($uri)->assertForbidden();
    }

    #[DataProvider('primaryAdminEndpoints')]
    public function test_primary_admin_endpoints_allow_the_primary_admin(string $uri): void
    {
        $admin = User::factory()->primaryAdmin()->create();

        $this->actingAs($admin)->getJson($uri)->assertOk();
    }

    public function test_profile_update_requires_authentication(): void
    {
        $this->putJson('/api/admin/profile', ['name' => 'Nama Baru'])
            ->assertUnauthorized();
    }

    public function test_profile_update_forbids_non_admin_users(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->putJson('/api/admin/profile', ['name' => 'Nama Baru'])
            ->assertForbidden();
    }

    public function test_profile_update_allows_every_admin(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->putJson('/api/admin/profile', ['name' => 'Nama Baru'])
            ->assertOk()
            ->assertJsonPath('user.name', 'Nama Baru');
    }
}
