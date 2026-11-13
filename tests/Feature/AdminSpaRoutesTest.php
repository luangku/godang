<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSpaRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_admin_to_login(): void
    {
        $this->get('/admin')
            ->assertRedirect('/admin/login');
    }

    public function test_login_page_is_public(): void
    {
        $this->get('/admin/login')
            ->assertOk()
            ->assertSee('admin-root', false);
    }

    public function test_admin_can_open_admin_shell(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/admin/posts')
            ->assertOk()
            ->assertSee('admin-root', false);
    }

    public function test_non_admin_user_gets_403_on_admin_shell(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/admin')
            ->assertForbidden();
    }
}
