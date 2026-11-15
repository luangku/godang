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
            'users index' => ['/api/admin/users'],
            'settings index' => ['/api/admin/settings'],
        ];
    }

    #[DataProvider('adminEndpoints')]
    public function test_admin_endpoints_require_authentication(string $uri): void
    {
        $this->getJson($uri)->assertUnauthorized();
    }

    #[DataProvider('adminEndpoints')]
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
}
