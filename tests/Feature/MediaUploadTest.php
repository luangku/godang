<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaUploadTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_upload_stores_file_in_public_disk_and_returns_relative_path(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $file = UploadedFile::fake()->image('foto.png', 1200, 800);

        $response = $this->actingAs($admin)
            ->postJson('/api/admin/media', ['file' => $file])
            ->assertCreated()
            ->assertJsonStructure(['message', 'media' => ['id', 'path', 'url'], 'url']);

        $media = $response->json('media');
        $this->assertStringStartsWith('uploads/', $media['path']);
        $this->assertStringEndsWith('.webp', $media['path']);
        Storage::disk('public')->assertExists($media['path']);
    }

    public function test_upload_creates_media_record_for_cover_folder(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $file = UploadedFile::fake()->image('cover.png', 1600, 900);

        $response = $this->actingAs($admin)
            ->postJson('/api/admin/media', ['file' => $file, 'folder' => 'cover'])
            ->assertCreated();

        $this->assertDatabaseHas('media', [
            'id' => $response->json('media.id'),
            'folder' => 'cover',
        ]);

        $path = $response->json('media.path');
        $this->assertStringStartsWith('cover/', $path);
        Storage::disk('public')->assertExists($path);
    }

    public function test_upload_rejects_image_formats_the_server_cannot_decode(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('foto.heic', 100)->mimeType('image/heic');

        $this->actingAs($this->admin())
            ->postJson('/api/admin/media', ['file' => $file])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('file');

        $this->assertDatabaseCount('media', 0);
    }

    public function test_upload_returns_a_helpful_error_when_the_file_cannot_be_decoded(): void
    {
        Storage::fake('public');

        // A non-empty file with a JPEG signature but truncated payload: it passes
        // the mime validation and only fails when the image is actually decoded.
        $file = UploadedFile::fake()
            ->createWithContent('rusak.jpg', "\xFF\xD8\xFF\xE0".str_repeat('x', 512))
            ->mimeType('image/jpeg');

        $response = $this->actingAs($this->admin())
            ->postJson('/api/admin/media', ['file' => $file])
            ->assertUnprocessable();

        $this->assertStringContainsString('tidak dapat diproses', $response->json('message'));
        $this->assertDatabaseCount('media', 0);
    }

    public function test_upload_returns_a_helpful_error_when_the_uploaded_file_is_empty(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->create('kosong.jpg', 0)->mimeType('image/jpeg');

        $response = $this->actingAs($this->admin())
            ->postJson('/api/admin/media', ['file' => $file])
            ->assertUnprocessable();

        $this->assertStringContainsString('tidak dapat dibaca', $response->json('message'));
        $this->assertDatabaseCount('media', 0);
    }

    public function test_media_index_returns_absolute_url_for_every_item(): void
    {
        $admin = $this->admin();
        Storage::fake('public');

        $media = Media::factory()->create([
            'path' => 'uploads/abc.webp',
            'folder' => 'uploads',
        ]);

        $this->actingAs($admin)
            ->getJson('/api/admin/media')
            ->assertOk()
            ->assertJsonPath('data.0.url', Storage::disk('public')->url($media->path));
    }

    public function test_delete_media_moves_it_to_trash_and_keeps_the_file(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        Storage::disk('public')->put('uploads/hapus.webp', 'data');
        $media = Media::factory()->create([
            'path' => 'uploads/hapus.webp',
            'folder' => 'uploads',
        ]);

        $this->actingAs($admin)
            ->deleteJson("/api/admin/media/{$media->id}")
            ->assertOk();

        Storage::disk('public')->assertExists('uploads/hapus.webp');
        $this->assertSoftDeleted('media', ['id' => $media->id]);
    }

    public function test_force_delete_media_removes_the_file_from_disk(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        Storage::disk('public')->put('uploads/hapus.webp', 'data');
        $media = Media::factory()->create([
            'path' => 'uploads/hapus.webp',
            'folder' => 'uploads',
        ]);
        $media->delete();

        $this->actingAs($admin)
            ->deleteJson("/api/admin/media/{$media->id}/force")
            ->assertOk();

        Storage::disk('public')->assertMissing('uploads/hapus.webp');
        $this->assertDatabaseMissing('media', ['id' => $media->id]);
    }
}
