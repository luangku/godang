<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\MediaCategory;
use App\Models\Post;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class AdminMediaTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_upload_converts_image_to_webp_and_scales_down_to_1600px(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $file = UploadedFile::fake()->image('besar.png', 3200, 1800);

        $response = $this->actingAs($admin)
            ->post('/api/admin/media', ['file' => $file], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('media.mime', 'image/webp')
            ->assertJsonPath('media.type', 'image')
            ->assertJsonPath('media.original_name', 'besar.png');

        $media = Media::findOrFail($response->json('media.id'));

        $this->assertSame(1600, $media->width);
        $this->assertSame(900, $media->height);
        $this->assertStringEndsWith('.webp', $media->path);
        $this->assertStringStartsWith('uploads/', $media->path);
        $this->assertNotNull($media->thumb_path);
        $this->assertStringEndsWith('-thumb.webp', $media->thumb_path);
        Storage::disk('public')->assertExists($media->path);
        Storage::disk('public')->assertExists($media->thumb_path);
    }

    public function test_upload_keeps_small_image_dimensions_but_still_writes_webp(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $file = UploadedFile::fake()->image('kecil.png', 800, 600);

        $response = $this->actingAs($admin)
            ->post('/api/admin/media', ['file' => $file], ['Accept' => 'application/json'])
            ->assertCreated();

        $media = Media::findOrFail($response->json('media.id'));

        $this->assertSame(800, $media->width);
        $this->assertSame(600, $media->height);
        $this->assertSame('image/webp', $media->mime);
    }

    public function test_upload_accepts_avatar_folder_and_index_can_filter_it(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $response = $this->actingAs($admin)
            ->post('/api/admin/media', [
                'file' => UploadedFile::fake()->image('avatar.png', 400, 400),
                'folder' => 'avatar',
            ], ['Accept' => 'application/json'])
            ->assertCreated();

        $media = Media::findOrFail($response->json('media.id'));

        $this->assertSame('avatar', $media->folder);
        $this->assertStringStartsWith('avatar/', $media->path);

        Media::create([
            'path' => 'uploads/lain.webp',
            'folder' => 'uploads',
            'alt_text' => 'Media lain',
            'mime' => 'image/webp',
            'size' => 100,
            'width' => 100,
            'height' => 100,
        ]);

        $this->actingAs($admin)
            ->getJson('/api/admin/media?folder=avatar')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.folder', 'avatar');
    }

    public function test_upload_assigns_custom_category_and_returns_category(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $category = MediaCategory::create(['name' => 'Ilustrasi']);

        $this->actingAs($admin)
            ->post('/api/admin/media', [
                'file' => UploadedFile::fake()->image('ilustrasi.png', 800, 600),
                'folder' => 'cover',
                'category_id' => $category->id,
            ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('media.category.id', $category->id)
            ->assertJsonPath('media.category.name', 'Ilustrasi');
    }

    public function test_upload_rejects_unknown_category(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $this->actingAs($admin)
            ->postJson('/api/admin/media', [
                'file' => UploadedFile::fake()->image('x.png', 100, 100),
                'category_id' => 999,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['category_id']);
    }

    public function test_upload_rejects_non_image_files(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $this->actingAs($admin)
            ->postJson('/api/admin/media', [
                'file' => UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);
    }

    public function test_upload_requires_admin(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/admin/media', [
                'file' => UploadedFile::fake()->image('x.png', 100, 100),
            ])
            ->assertForbidden();
    }

    public function test_index_separates_images_and_files_by_type(): void
    {
        $admin = $this->admin();
        $image = Media::factory()->create(['alt_text' => 'Gambar']);
        $file = Media::factory()->file()->create(['alt_text' => 'Dokumen']);

        $this->actingAs($admin)
            ->getJson('/api/admin/media?type=image')
            ->assertOk()
            ->assertJsonPath('type', 'image')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $image->id);

        $this->actingAs($admin)
            ->getJson('/api/admin/media?type=file')
            ->assertOk()
            ->assertJsonPath('type', 'file')
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $file->id)
            ->assertJsonPath('data.0.download_name', 'dokumen.pdf');
    }

    public function test_upload_stores_non_image_file_with_original_name(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $category = MediaCategory::create(['name' => 'Dokumen']);

        $response = $this->actingAs($admin)
            ->post('/api/admin/media', [
                'file' => UploadedFile::fake()->create('laporan-2026.pdf', 500, 'application/pdf'),
                'type' => 'file',
                'category_id' => $category->id,
            ], ['Accept' => 'application/json'])
            ->assertCreated()
            ->assertJsonPath('media.type', 'file')
            ->assertJsonPath('media.original_name', 'laporan-2026.pdf')
            ->assertJsonPath('media.download_name', 'laporan-2026.pdf')
            ->assertJsonPath('media.mime', 'application/pdf')
            ->assertJsonPath('media.folder', 'files')
            ->assertJsonPath('media.category.id', $category->id);

        $media = Media::findOrFail($response->json('media.id'));

        $this->assertStringStartsWith('files/', $media->path);
        $this->assertStringEndsWith('.pdf', $media->path);
        $this->assertNull($media->thumb_path);
        $this->assertNull($media->width);
        $this->assertNull($media->height);
        Storage::disk('public')->assertExists($media->path);
    }

    public function test_file_upload_rejects_dangerous_extensions(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $dangerous = [
            ['shell.php', 'application/x-php'],
            ['halaman.html', 'text/html'],
            ['gambar.svg', 'image/svg+xml'],
            ['script.js', 'application/javascript'],
            ['program.exe', 'application/x-msdownload'],
        ];

        foreach ($dangerous as [$name, $mime]) {
            $this->actingAs($admin)
                ->postJson('/api/admin/media', [
                    'file' => UploadedFile::fake()->create($name, 10, $mime),
                    'type' => 'file',
                ])
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['file']);
        }

        $this->assertSame(0, Media::count());
    }

    public function test_file_upload_rejects_mismatched_mime_type(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $this->actingAs($admin)
            ->postJson('/api/admin/media', [
                'file' => UploadedFile::fake()->create('palsu.pdf', 10, 'text/html'),
                'type' => 'file',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);

        $this->assertSame(0, Media::count());
    }

    public function test_file_upload_rejects_files_larger_than_10_mb(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $this->actingAs($admin)
            ->postJson('/api/admin/media', [
                'file' => UploadedFile::fake()->create('besar.pdf', 10241, 'application/pdf'),
                'type' => 'file',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);

        $this->assertSame(0, Media::count());
    }

    public function test_file_index_can_search_by_original_name(): void
    {
        $admin = $this->admin();
        Media::factory()->file()->create(['original_name' => 'laporan-tahunan.pdf', 'alt_text' => 'Laporan']);
        Media::factory()->file()->create(['original_name' => 'notulen.docx', 'alt_text' => 'Notulen']);

        $this->actingAs($admin)
            ->getJson('/api/admin/media?type=file&q=tahunan')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.download_name', 'laporan-tahunan.pdf');
    }

    public function test_force_delete_removes_non_image_file(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        Storage::disk('public')->put('files/dokumen.pdf', 'binary-data');

        $media = Media::factory()->file()->create(['path' => 'files/dokumen.pdf']);
        $media->delete();

        $this->actingAs($admin)
            ->deleteJson("/api/admin/media/{$media->id}/force")
            ->assertOk();

        $this->assertDatabaseMissing('media', ['id' => $media->id]);
        Storage::disk('public')->assertMissing('files/dokumen.pdf');
    }

    public function test_index_lists_media_with_search_filter(): void
    {
        $admin = $this->admin();
        Media::create([
            'path' => 'uploads/satu.webp',
            'folder' => 'uploads',
            'alt_text' => 'Gambar pertama',
            'mime' => 'image/webp',
            'size' => 1000,
            'width' => 800,
            'height' => 600,
        ]);
        Media::create([
            'path' => 'uploads/dua.webp',
            'folder' => 'uploads',
            'alt_text' => 'Ilustrasi kedua',
            'mime' => 'image/webp',
            'size' => 2000,
            'width' => 1024,
            'height' => 768,
        ]);

        $this->actingAs($admin)
            ->getJson('/api/admin/media')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.1.url', Storage::disk('public')->url('uploads/satu.webp'))
            ->assertJsonPath('data.0.url', Storage::disk('public')->url('uploads/dua.webp'))
            ->assertJsonStructure(['data' => [['id', 'path', 'url', 'thumb_url', 'folder']]]);

        $this->actingAs($admin)
            ->getJson('/api/admin/media?q=pertama')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.alt_text', 'Gambar pertama');
    }

    public function test_index_returns_folder_counts_for_the_current_search(): void
    {
        $admin = $this->admin();

        foreach ([
            ['folder' => 'uploads', 'alt_text' => 'Laporan utama'],
            ['folder' => 'cover', 'alt_text' => 'Laporan cover'],
            ['folder' => 'legacy', 'alt_text' => 'Laporan lama'],
            ['folder' => 'avatar', 'alt_text' => 'Foto profil'],
        ] as $index => $attributes) {
            Media::create([
                'path' => $attributes['folder']."/file-{$index}.webp",
                'folder' => $attributes['folder'],
                'alt_text' => $attributes['alt_text'],
                'mime' => 'image/webp',
                'size' => 1000,
                'width' => 800,
                'height' => 600,
            ]);
        }

        $this->actingAs($admin)
            ->getJson('/api/admin/media?q=Laporan')
            ->assertOk()
            ->assertJsonPath('total', 3)
            ->assertJsonPath('folders.all', 3)
            ->assertJsonPath('folders.uploads', 1)
            ->assertJsonPath('folders.cover', 1)
            ->assertJsonPath('folders.avatar', 0)
            ->assertJsonPath('folders.other', 1);
    }

    public function test_index_filters_legacy_folders_as_other(): void
    {
        $admin = $this->admin();

        Media::create([
            'path' => 'uploads/normal.webp',
            'folder' => 'uploads',
            'alt_text' => 'Normal',
            'mime' => 'image/webp',
            'size' => 1000,
            'width' => 800,
            'height' => 600,
        ]);
        Media::create([
            'path' => 'legacy/lama.webp',
            'folder' => 'legacy',
            'alt_text' => 'Lama',
            'mime' => 'image/webp',
            'size' => 1000,
            'width' => 800,
            'height' => 600,
        ]);

        $this->actingAs($admin)
            ->getJson('/api/admin/media?folder=other')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.folder', 'legacy')
            ->assertJsonPath('folders.other', 1);
    }

    public function test_index_sorts_media_by_size_and_name(): void
    {
        $admin = $this->admin();

        foreach ([
            ['alt_text' => 'Zebra', 'size' => 100],
            ['alt_text' => 'Apel', 'size' => 2000],
            ['alt_text' => 'Mangga', 'size' => 1000],
        ] as $index => $attributes) {
            Media::create([
                'path' => "uploads/sort-{$index}.webp",
                'folder' => 'uploads',
                'alt_text' => $attributes['alt_text'],
                'mime' => 'image/webp',
                'size' => $attributes['size'],
                'width' => 800,
                'height' => 600,
            ]);
        }

        $this->actingAs($admin)
            ->getJson('/api/admin/media?sort=smallest')
            ->assertOk()
            ->assertJsonPath('data.0.alt_text', 'Zebra');

        $this->actingAs($admin)
            ->getJson('/api/admin/media?sort=largest')
            ->assertOk()
            ->assertJsonPath('data.0.alt_text', 'Apel');

        $this->actingAs($admin)
            ->getJson('/api/admin/media?sort=name')
            ->assertOk()
            ->assertJsonPath('data.0.alt_text', 'Apel')
            ->assertJsonPath('data.1.alt_text', 'Mangga')
            ->assertJsonPath('data.2.alt_text', 'Zebra');
    }

    public function test_index_clamps_per_page_between_1_and_50(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->getJson('/api/admin/media?per_page=999')
            ->assertOk()
            ->assertJsonPath('per_page', 50);

        $this->actingAs($admin)
            ->getJson('/api/admin/media?per_page=0')
            ->assertOk()
            ->assertJsonPath('per_page', 1);
    }

    public function test_index_filters_by_custom_category_and_returns_category_counts(): void
    {
        $admin = $this->admin();
        $ilustrasi = MediaCategory::create(['name' => 'Ilustrasi']);
        $dokumentasi = MediaCategory::create(['name' => 'Dokumentasi']);

        Media::factory()->create([
            'path' => 'uploads/ilustrasi-1.webp',
            'folder' => 'uploads',
            'media_category_id' => $ilustrasi->id,
        ]);
        Media::factory()->create([
            'path' => 'cover/ilustrasi-2.webp',
            'folder' => 'cover',
            'media_category_id' => $ilustrasi->id,
        ]);
        Media::factory()->create([
            'path' => 'uploads/dokumentasi-1.webp',
            'folder' => 'uploads',
            'media_category_id' => $dokumentasi->id,
        ]);
        Media::factory()->create([
            'path' => 'uploads/tanpa-kategori.webp',
            'folder' => 'uploads',
            'media_category_id' => null,
        ]);

        $this->actingAs($admin)
            ->getJson("/api/admin/media?category={$ilustrasi->id}")
            ->assertOk()
            ->assertJsonPath('total', 2);

        $this->actingAs($admin)
            ->getJson('/api/admin/media?category=none')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.path', 'uploads/tanpa-kategori.webp')
            ->assertJsonPath('uncategorized_count', 1);

        $response = $this->actingAs($admin)
            ->getJson('/api/admin/media?folder=uploads')
            ->assertOk();

        $counts = collect($response->json('categories'))->pluck('count', 'name');

        $this->assertSame(1, $counts['Ilustrasi']);
        $this->assertSame(1, $counts['Dokumentasi']);
        $this->assertSame(1, $response->json('uncategorized_count'));
    }

    public function test_index_reports_usage_and_can_filter_unused_media(): void
    {
        $admin = $this->admin();
        $category = MediaCategory::create(['name' => 'Ilustrasi']);
        $used = Media::factory()->create([
            'path' => 'uploads/used.webp',
            'folder' => 'uploads',
            'media_category_id' => $category->id,
        ]);
        $unused = Media::factory()->create([
            'path' => 'cover/unused.webp',
            'folder' => 'cover',
            'media_category_id' => $category->id,
        ]);

        $post = Post::factory()->create([
            'cover_image' => $used->path,
            'content' => "<p>Konten <img src=\"/storage/{$used->path}\"></p>",
        ]);
        $post->delete();

        Setting::setValue('author_avatar', $used->path);
        Setting::setValue('site_logo', $used->path);

        $all = $this->actingAs($admin)->getJson('/api/admin/media')->assertOk();
        $byPath = collect($all->json('data'))->keyBy('path');

        $this->assertSame(4, $byPath[$used->path]['usage_count']);
        $this->assertContains('Logo situs', $byPath[$used->path]['usage_labels']);
        $this->assertSame(0, $byPath[$unused->path]['usage_count']);
        $this->assertSame(1, $all->json('unused_count'));

        $filtered = $this->actingAs($admin)
            ->getJson('/api/admin/media?unused=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $unused->id)
            ->assertJsonPath('unused_count', 1)
            ->assertJsonPath('folders.all', 1)
            ->assertJsonPath('folders.cover', 1)
            ->assertJsonPath('folders.uploads', 0);

        $counts = collect($filtered->json('categories'))->pluck('count', 'name');

        $this->assertSame(1, $counts['Ilustrasi']);
    }

    public function test_update_changes_alt_text_and_category(): void
    {
        $admin = $this->admin();
        $category = MediaCategory::create(['name' => 'Ilustrasi']);
        $media = Media::factory()->create(['media_category_id' => null]);

        $this->actingAs($admin)
            ->patchJson("/api/admin/media/{$media->id}", [
                'alt_text' => 'Alt text baru',
                'category_id' => $category->id,
            ])
            ->assertOk()
            ->assertJsonPath('media.alt_text', 'Alt text baru')
            ->assertJsonPath('media.category.id', $category->id);

        $this->actingAs($admin)
            ->patchJson("/api/admin/media/{$media->id}", ['category_id' => null])
            ->assertOk()
            ->assertJsonPath('media.category', null);
    }

    public function test_destroy_moves_media_to_trash_and_keeps_file(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        $name = Str::uuid().'.webp';
        Storage::disk('public')->put('uploads/'.$name, 'binary-data');

        $media = Media::create([
            'path' => 'uploads/'.$name,
            'folder' => 'uploads',
            'alt_text' => 'Hapus saya',
            'mime' => 'image/webp',
            'size' => 11,
            'width' => 100,
            'height' => 100,
        ]);

        $this->actingAs($admin)
            ->deleteJson("/api/admin/media/{$media->id}")
            ->assertOk()
            ->assertJsonPath('usage.count', 0);

        Storage::disk('public')->assertExists('uploads/'.$name);
        $this->assertSoftDeleted('media', ['id' => $media->id]);

        $this->actingAs($admin)
            ->getJson('/api/admin/media')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->actingAs($admin)
            ->getJson('/api/admin/media?trashed=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $media->id)
            ->assertJsonPath('trashed', true);
    }

    public function test_soft_delete_warns_when_media_is_still_used(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        Storage::disk('public')->put('uploads/used.webp', 'binary-data');
        $media = Media::factory()->create(['path' => 'uploads/used.webp']);
        Post::factory()->create(['cover_image' => $media->path]);

        $response = $this->actingAs($admin)
            ->deleteJson("/api/admin/media/{$media->id}")
            ->assertOk()
            ->assertJsonPath('usage.count', 1);

        $this->assertStringContainsString('masih dipakai', $response->json('message'));
        $this->assertSoftDeleted('media', ['id' => $media->id]);
        Storage::disk('public')->assertExists('uploads/used.webp');
    }

    public function test_restore_brings_media_back_from_trash(): void
    {
        $admin = $this->admin();
        $media = Media::factory()->create();
        $media->delete();

        $this->actingAs($admin)
            ->postJson("/api/admin/media/{$media->id}/restore")
            ->assertOk()
            ->assertJsonPath('media.id', $media->id);

        $this->assertNotSoftDeleted('media', ['id' => $media->id]);
    }

    public function test_force_delete_is_blocked_while_media_is_used(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        Storage::disk('public')->put('uploads/used.webp', 'binary-data');
        $media = Media::factory()->create(['path' => 'uploads/used.webp']);
        Post::factory()->create(['cover_image' => $media->path]);
        $media->delete();

        $this->actingAs($admin)
            ->deleteJson("/api/admin/media/{$media->id}/force")
            ->assertStatus(409)
            ->assertJsonPath('usage.count', 1);

        $this->assertSoftDeleted('media', ['id' => $media->id]);
        Storage::disk('public')->assertExists('uploads/used.webp');
    }

    public function test_force_delete_removes_unused_media_and_thumbnail(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        Storage::disk('public')->put('uploads/bersih.webp', 'original');
        Storage::disk('public')->put('uploads/bersih-thumb.webp', 'thumb');

        $media = Media::factory()->create([
            'path' => 'uploads/bersih.webp',
            'thumb_path' => 'uploads/bersih-thumb.webp',
        ]);
        $media->delete();

        $this->actingAs($admin)
            ->deleteJson("/api/admin/media/{$media->id}/force")
            ->assertOk();

        $this->assertDatabaseMissing('media', ['id' => $media->id]);
        Storage::disk('public')->assertMissing('uploads/bersih.webp');
        Storage::disk('public')->assertMissing('uploads/bersih-thumb.webp');
    }

    public function test_bulk_delete_restore_and_force_delete(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $first = Media::factory()->create();
        $second = Media::factory()->create();

        $this->actingAs($admin)
            ->postJson('/api/admin/media/bulk-delete', ['ids' => [$first->id, $second->id]])
            ->assertOk();

        $this->assertSoftDeleted('media', ['id' => $first->id]);
        $this->assertSoftDeleted('media', ['id' => $second->id]);

        $this->actingAs($admin)
            ->postJson('/api/admin/media/bulk-restore', ['ids' => [$first->id, $second->id]])
            ->assertOk();

        $this->assertNotSoftDeleted('media', ['id' => $first->id]);
        $this->assertNotSoftDeleted('media', ['id' => $second->id]);

        $this->actingAs($admin)
            ->postJson('/api/admin/media/bulk-delete', ['ids' => [$first->id, $second->id]])
            ->assertOk();

        $this->actingAs($admin)
            ->postJson('/api/admin/media/bulk-force-delete', ['ids' => [$first->id, $second->id]])
            ->assertOk();

        $this->assertDatabaseMissing('media', ['id' => $first->id]);
        $this->assertDatabaseMissing('media', ['id' => $second->id]);
    }

    public function test_bulk_force_delete_is_blocked_if_any_media_is_used(): void
    {
        Storage::fake('public');
        $admin = $this->admin();
        $used = Media::factory()->create(['path' => 'uploads/used.webp']);
        $unused = Media::factory()->create(['path' => 'uploads/unused.webp']);
        Post::factory()->create(['cover_image' => $used->path]);
        $used->delete();
        $unused->delete();

        $this->actingAs($admin)
            ->postJson('/api/admin/media/bulk-force-delete', ['ids' => [$used->id, $unused->id]])
            ->assertStatus(409)
            ->assertJsonCount(1, 'blocked')
            ->assertJsonPath('blocked.0.id', $used->id);

        $this->assertDatabaseHas('media', ['id' => $used->id]);
        $this->assertDatabaseHas('media', ['id' => $unused->id]);
    }

    public function test_media_category_crud_and_uncategorizes_media_on_delete(): void
    {
        $admin = $this->admin();

        $created = $this->actingAs($admin)
            ->postJson('/api/admin/media-categories', ['name' => 'Ilustrasi'])
            ->assertCreated()
            ->assertJsonPath('category.name', 'Ilustrasi');

        $categoryId = $created->json('category.id');
        $media = Media::factory()->create(['media_category_id' => $categoryId]);

        $this->actingAs($admin)
            ->getJson('/api/admin/media-categories')
            ->assertOk()
            ->assertJsonPath('0.name', 'Ilustrasi')
            ->assertJsonPath('0.media_count', 1);

        $this->actingAs($admin)
            ->patchJson("/api/admin/media-categories/{$categoryId}", ['name' => 'Ilustrasi Blog'])
            ->assertOk()
            ->assertJsonPath('category.name', 'Ilustrasi Blog');

        $this->actingAs($admin)
            ->postJson('/api/admin/media-categories', ['name' => 'Ilustrasi Blog'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['name']);

        $this->actingAs($admin)
            ->deleteJson("/api/admin/media-categories/{$categoryId}")
            ->assertOk();

        $this->assertDatabaseMissing('media_categories', ['id' => $categoryId]);
        $this->assertDatabaseHas('media', ['id' => $media->id, 'media_category_id' => null]);
    }

    public function test_thumbnail_command_backfills_existing_media(): void
    {
        Storage::fake('public');
        Storage::disk('public')->makeDirectory('uploads');

        $absolutePath = Storage::disk('public')->path('uploads/lama.webp');
        $image = imagecreatetruecolor(800, 600);
        imagewebp($image, $absolutePath);
        imagedestroy($image);

        $media = Media::factory()->create([
            'path' => 'uploads/lama.webp',
            'thumb_path' => null,
        ]);

        $this->artisan('media:thumbnails')->assertSuccessful();

        $media->refresh();

        $this->assertSame('uploads/lama-thumb.webp', $media->thumb_path);
        Storage::disk('public')->assertExists('uploads/lama-thumb.webp');
    }

    public function test_index_normalizes_legacy_storage_prefixed_media_path(): void
    {
        $admin = $this->admin();

        Media::create([
            'path' => 'storage/uploads/legacy.webp',
            'folder' => 'uploads',
            'alt_text' => 'Path lama',
            'mime' => 'image/webp',
            'size' => 1000,
            'width' => 800,
            'height' => 600,
        ]);

        $this->actingAs($admin)
            ->getJson('/api/admin/media')
            ->assertOk()
            ->assertJsonPath('data.0.url', Storage::disk('public')->url('uploads/legacy.webp'));
    }

    public function test_index_normalizes_absolute_storage_url_media_path(): void
    {
        $admin = $this->admin();

        Media::create([
            'path' => 'http://legacy.test/storage/uploads/absolute.webp',
            'folder' => 'uploads',
            'alt_text' => 'URL absolut',
            'mime' => 'image/webp',
            'size' => 1000,
            'width' => 800,
            'height' => 600,
        ]);

        $this->actingAs($admin)
            ->getJson('/api/admin/media')
            ->assertOk()
            ->assertJsonPath('data.0.url', Storage::disk('public')->url('uploads/absolute.webp'));
    }

    public function test_force_delete_removes_file_when_path_has_storage_prefix(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        Storage::disk('public')->put('uploads/legacy.webp', 'binary-data');

        $media = Media::create([
            'path' => 'storage/uploads/legacy.webp',
            'folder' => 'uploads',
            'alt_text' => 'Path lama',
            'mime' => 'image/webp',
            'size' => 11,
            'width' => 100,
            'height' => 100,
        ]);

        $this->actingAs($admin)
            ->deleteJson("/api/admin/media/{$media->id}")
            ->assertOk();

        $this->actingAs($admin)
            ->deleteJson("/api/admin/media/{$media->id}/force")
            ->assertOk();

        Storage::disk('public')->assertMissing('uploads/legacy.webp');
        $this->assertDatabaseMissing('media', ['id' => $media->id]);
    }

    public function test_force_delete_removes_file_when_path_is_absolute_storage_url(): void
    {
        Storage::fake('public');
        $admin = $this->admin();

        Storage::disk('public')->put('uploads/absolute.webp', 'binary-data');

        $media = Media::create([
            'path' => rtrim((string) config('app.url'), '/').'/storage/uploads/absolute.webp',
            'folder' => 'uploads',
            'alt_text' => 'URL absolut',
            'mime' => 'image/webp',
            'size' => 11,
            'width' => 100,
            'height' => 100,
        ]);

        $this->actingAs($admin)
            ->deleteJson("/api/admin/media/{$media->id}")
            ->assertOk();

        $this->actingAs($admin)
            ->deleteJson("/api/admin/media/{$media->id}/force")
            ->assertOk();

        Storage::disk('public')->assertMissing('uploads/absolute.webp');
        $this->assertDatabaseMissing('media', ['id' => $media->id]);
    }
}
