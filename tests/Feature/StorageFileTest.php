<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StorageFileTest extends TestCase
{
    use RefreshDatabase;

    public function test_storage_route_serves_an_uploaded_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('cover/test.webp', 'fake-image-content');

        $response = $this->get('/storage/cover/test.webp');

        $response->assertOk();
    }

    public function test_storage_route_serves_an_avatar_file(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('avatar/test.webp', 'fake-image-content');

        $this->get('/storage/avatar/test.webp')->assertOk();
    }

    public function test_storage_route_serves_a_non_image_file_with_nosniff(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('files/laporan.pdf', 'fake-pdf-content');

        $this->get('/storage/files/laporan.pdf')
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Content-Disposition', 'inline; filename="laporan.pdf"');
    }

    public function test_storage_route_serves_nested_legacy_paths(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('uploads/2024/foto-lama.jpg', 'fake-image-content');

        $this->get('/storage/uploads/2024/foto-lama.jpg')->assertOk();
    }

    public function test_storage_route_rejects_unsupported_file_extensions(): void
    {
        Storage::fake('public');

        foreach (['uploads/shell.php', 'uploads/halaman.html', 'uploads/gambar.svg', 'uploads/script.js'] as $path) {
            Storage::disk('public')->put($path, 'dangerous');
            $this->get('/storage/'.$path)->assertNotFound();
        }
    }

    public function test_storage_route_rejects_hidden_files(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('uploads/.rahasia.jpg', 'fake-image-content');

        $this->get('/storage/uploads/.rahasia.jpg')->assertNotFound();
    }

    public function test_storage_route_returns_404_for_unknown_file(): void
    {
        Storage::fake('public');

        $this->get('/storage/cover/tidak-ada.webp')->assertNotFound();
    }

    public function test_storage_route_rejects_path_traversal(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('cover/ok.webp', 'x');

        $this->get('/storage/../cover/ok.webp')->assertNotFound();
        $this->get('/storage/cover/../../.env')->assertNotFound();
    }
}
