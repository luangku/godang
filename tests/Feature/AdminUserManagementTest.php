<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_lists_admin_accounts_with_post_counts(): void
    {
        $admin = User::factory()->admin()->create(['name' => 'Admin Utama']);
        User::factory()->create(['name' => 'Pembaca']);
        Post::factory()->create(['user_id' => $admin->id]);

        $this->actingAs($admin)
            ->getJson('/api/admin/users')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.name', 'Admin Utama')
            ->assertJsonPath('0.posts_count', 1)
            ->assertJsonMissingPath('0.password');
    }

    public function test_store_creates_a_new_admin_account(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->postJson('/api/admin/users', [
                'name' => 'Admin Baru',
                'email' => 'admin-baru@example.com',
                'password' => 'rahasia-123',
                'password_confirmation' => 'rahasia-123',
            ])
            ->assertCreated()
            ->assertJsonPath('user.name', 'Admin Baru')
            ->assertJsonPath('user.email', 'admin-baru@example.com')
            ->assertJsonMissingPath('user.password');

        $created = User::where('email', 'admin-baru@example.com')->firstOrFail();

        $this->assertTrue($created->is_admin);
        $this->assertTrue(Hash::check('rahasia-123', $created->password));
    }

    public function test_store_rejects_a_duplicate_email(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->admin()->create(['email' => 'dipakai@example.com']);

        $this->actingAs($admin)
            ->postJson('/api/admin/users', [
                'name' => 'Admin Lain',
                'email' => 'dipakai@example.com',
                'password' => 'rahasia-123',
                'password_confirmation' => 'rahasia-123',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_store_requires_a_confirmed_password(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->postJson('/api/admin/users', [
                'name' => 'Admin Baru',
                'email' => 'admin-baru@example.com',
                'password' => 'rahasia-123',
                'password_confirmation' => 'beda-123',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }

    public function test_update_changes_name_and_email_without_touching_the_password(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->admin()->create(['password' => 'password-lama']);
        $originalPassword = $target->password;

        $this->actingAs($admin)
            ->putJson("/api/admin/users/{$target->id}", [
                'name' => 'Nama Baru',
                'email' => 'email-baru@example.com',
            ])
            ->assertOk()
            ->assertJsonPath('user.name', 'Nama Baru')
            ->assertJsonPath('user.email', 'email-baru@example.com');

        $target->refresh();

        $this->assertSame($originalPassword, $target->password);
        $this->assertTrue(Hash::check('password-lama', $target->password));
    }

    public function test_update_replaces_the_password_when_provided(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->putJson("/api/admin/users/{$target->id}", [
                'password' => 'password-baru',
                'password_confirmation' => 'password-baru',
            ])
            ->assertOk();

        $this->assertTrue(Hash::check('password-baru', $target->fresh()->password));
    }

    public function test_update_rejects_an_email_used_by_another_account(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->admin()->create(['email' => 'dipakai@example.com']);
        $target = User::factory()->admin()->create(['email' => 'target@example.com']);

        $this->actingAs($admin)
            ->putJson("/api/admin/users/{$target->id}", ['email' => 'dipakai@example.com'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_update_returns_404_for_non_admin_users(): void
    {
        $admin = User::factory()->admin()->create();
        $reader = User::factory()->create();

        $this->actingAs($admin)
            ->putJson("/api/admin/users/{$reader->id}", ['name' => 'Bukan Admin'])
            ->assertNotFound();
    }

    public function test_destroy_removes_an_admin_without_posts(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->deleteJson("/api/admin/users/{$target->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Admin berhasil dihapus.');

        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }

    public function test_destroy_rejects_deleting_your_own_account(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->deleteJson("/api/admin/users/{$admin->id}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['user']);

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_destroy_rejects_an_admin_that_still_owns_posts(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->admin()->create();
        Post::factory()->create(['user_id' => $target->id]);

        $this->actingAs($admin)
            ->deleteJson("/api/admin/users/{$target->id}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['user']);

        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }

    public function test_destroy_rejects_an_admin_with_only_trashed_posts(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->admin()->create();
        $post = Post::factory()->create(['user_id' => $target->id]);
        $post->delete();

        $this->actingAs($admin)
            ->deleteJson("/api/admin/users/{$target->id}")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['user']);

        $this->assertDatabaseHas('users', ['id' => $target->id]);
    }
}
