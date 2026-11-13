<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCategoryTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_store_persists_name_and_description(): void
    {
        $this->actingAs($this->admin())
            ->postJson('/api/admin/categories', [
                'name' => 'Laravel',
                'description' => 'Framework PHP untuk aplikasi web modern.',
            ])
            ->assertCreated()
            ->assertJsonPath('name', 'Laravel')
            ->assertJsonPath('description', 'Framework PHP untuk aplikasi web modern.');
    }

    public function test_update_can_change_and_clear_description(): void
    {
        $category = Category::factory()->create([
            'description' => 'Deskripsi lama.',
        ]);

        $this->actingAs($this->admin())
            ->putJson("/api/admin/categories/{$category->id}", [
                'description' => 'Deskripsi baru.',
            ])
            ->assertOk()
            ->assertJsonPath('description', 'Deskripsi baru.');

        $this->assertSame('Deskripsi baru.', $category->fresh()->description);

        $this->actingAs($this->admin())
            ->putJson("/api/admin/categories/{$category->id}", ['description' => null])
            ->assertOk();

        $this->assertNull($category->fresh()->description);
    }
}
