<?php

namespace App\Console\Commands;

use App\Models\Media;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

#[Signature('media:backfill {--force : Run without confirmation} {--prune-missing : Clear post image references whose local file no longer exists}')]
#[Description('Normalize legacy media paths and register orphaned media files.')]
class BackfillMediaPaths extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('Normalize legacy media paths and register orphaned media files?')) {
            return self::FAILURE;
        }

        $postsUpdated = $this->normalizePostImages();
        $mediaUpdated = $this->normalizeMediaPaths();
        $mediaCreated = $this->registerOrphanedFiles();
        $referencesCleared = $this->option('prune-missing') ? $this->pruneMissingPostImages() : 0;

        $this->info("Posts updated: {$postsUpdated}");
        $this->info("Media paths normalized: {$mediaUpdated}");
        $this->info("Media records created: {$mediaCreated}");
        $this->info("Missing post image references cleared: {$referencesCleared}");

        return self::SUCCESS;
    }

    /**
     * Convert absolute storage URLs stored on posts into relative disk paths.
     */
    private function normalizePostImages(): int
    {
        $updated = 0;

        DB::table('posts')
            ->select('id', 'cover_image', 'og_image')
            ->orderBy('id')
            ->chunkById(100, function ($posts) use (&$updated) {
                foreach ($posts as $post) {
                    $changes = [];

                    foreach (['cover_image', 'og_image'] as $column) {
                        $normalized = $this->normalizeValue($post->{$column});

                        if ($normalized !== $post->{$column}) {
                            $changes[$column] = $normalized;
                        }
                    }

                    if ($changes !== []) {
                        DB::table('posts')->where('id', $post->id)->update($changes);
                        $updated++;
                    }
                }
            });

        return $updated;
    }

    /**
     * Strip the legacy "storage/" prefix from media paths.
     */
    private function normalizeMediaPaths(): int
    {
        $updated = 0;

        DB::table('media')
            ->select('id', 'path', 'folder')
            ->orderBy('id')
            ->chunkById(100, function ($rows) use (&$updated) {
                foreach ($rows as $row) {
                    $path = $this->normalizeValue($row->path);

                    if ($path === $row->path) {
                        continue;
                    }

                    $folder = $row->folder ?: (str_starts_with($path, 'cover/') ? 'cover' : 'uploads');

                    DB::table('media')->where('id', $row->id)->update([
                        'path' => $path,
                        'folder' => $folder,
                    ]);
                    $updated++;
                }
            });

        return $updated;
    }

    /**
     * Insert media records for files on the public disk that are not registered yet.
     */
    private function registerOrphanedFiles(): int
    {
        $created = 0;

        do {
            $result = Media::scanPublicDisk(100, false);
            $created += $result['created'];
        } while ($result['remaining'] > 0);

        return $created;
    }

    /**
     * Clear post image references whose local file no longer exists.
     */
    private function pruneMissingPostImages(): int
    {
        $cleared = 0;
        $disk = Storage::disk('public');

        DB::table('posts')
            ->select('id', 'cover_image', 'og_image')
            ->orderBy('id')
            ->chunkById(100, function ($posts) use (&$cleared, $disk) {
                foreach ($posts as $post) {
                    $changes = [];

                    foreach (['cover_image', 'og_image'] as $column) {
                        $value = $post->{$column};

                        if (! $value) {
                            continue;
                        }

                        $path = Media::diskPath($value);

                        if ($path !== null && ! $disk->exists($path)) {
                            $changes[$column] = null;
                        }
                    }

                    if ($changes !== []) {
                        DB::table('posts')->where('id', $post->id)->update($changes);
                        $cleared++;
                    }
                }
            });

        return $cleared;
    }

    /**
     * Normalize a stored media value into a path relative to the public disk.
     */
    private function normalizeValue(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        if (preg_match('#^https?://.+/storage/(.+)$#i', $value, $matches)) {
            return ltrim($matches[1], '/');
        }

        if (str_starts_with($value, 'storage/')) {
            return substr($value, strlen('storage/'));
        }

        return $value;
    }
}
