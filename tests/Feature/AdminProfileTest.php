<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_their_own_name_and_email(): void
    {
        $admin = User::factory()->admin()->create([
            'name' => 'Nama Lama',
            'email' => 'lama@example.com',
        ]);

        $this->actingAs($admin)
            ->putJson('/api/admin/profile', [
                'name' => 'Nama Baru',
                'email' => 'baru@example.com',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Profil berhasil diperbarui.')
            ->assertJsonPath('user.name', 'Nama Baru')
            ->assertJsonPath('user.email', 'baru@example.com');

        $this->assertDatabaseHas('users', [
            'id' => $admin->id,
            'name' => 'Nama Baru',
            'email' => 'baru@example.com',
        ]);
    }

    public function test_admin_can_update_their_own_password(): void
    {
        $admin = User::factory()->admin()->create(['password' => 'password-lama']);

        $this->actingAs($admin)
            ->putJson('/api/admin/profile', [
                'password' => 'password-baru',
                'password_confirmation' => 'password-baru',
            ])
            ->assertOk();

        $this->assertTrue(Hash::check('password-baru', $admin->fresh()->password));
    }

    public function test_password_is_left_untouched_when_omitted(): void
    {
        $admin = User::factory()->admin()->create(['password' => 'password-lama']);
        $originalPassword = $admin->password;

        $this->actingAs($admin)
            ->putJson('/api/admin/profile', ['name' => 'Nama Baru'])
            ->assertOk();

        $this->assertSame($originalPassword, $admin->fresh()->password);
        $this->assertTrue(Hash::check('password-lama', $admin->fresh()->password));
    }

    public function test_password_requires_a_matching_confirmation(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->putJson('/api/admin/profile', [
                'password' => 'password-baru',
                'password_confirmation' => 'beda-password',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }

    public function test_password_must_be_at_least_eight_characters(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->putJson('/api/admin/profile', [
                'password' => 'pendek',
                'password_confirmation' => 'pendek',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }

    public function test_email_must_be_unique(): void
    {
        User::factory()->create(['email' => 'dipakai@example.com']);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->putJson('/api/admin/profile', ['email' => 'dipakai@example.com'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_admin_can_keep_their_current_email(): void
    {
        $admin = User::factory()->admin()->create(['email' => 'saya@example.com']);

        $this->actingAs($admin)
            ->putJson('/api/admin/profile', [
                'name' => 'Nama Baru',
                'email' => 'saya@example.com',
            ])
            ->assertOk()
            ->assertJsonPath('user.email', 'saya@example.com');
    }

    public function test_profile_update_cannot_change_role_flags(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->putJson('/api/admin/profile', [
                'name' => 'Nama Baru',
                'is_admin' => false,
                'is_primary' => true,
            ])
            ->assertOk();

        $admin->refresh();

        $this->assertTrue($admin->is_admin);
        $this->assertFalse($admin->is_primary);
    }
}
