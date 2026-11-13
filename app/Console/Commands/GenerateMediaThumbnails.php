<?php

namespace App\Console\Commands;

use App\Models\Media;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Throwable;

#[Signature('media:thumbnails {--force : Regenerate thumbnails that already exist}')]
#[Description('Generate WebP thumbnails for existing media files.')]
class GenerateMediaThumbnails extends Command
{
    /**
     * Maximum thumbnail width/height in pixels.
     */
    private const THUMB_DIMENSION = 480;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $disk = Storage::disk('public');
        $created = 0;
        $skipped = 0;
        $failed = 0;

        Media::withTrashed()
            ->orderBy('id')
            ->chunkById(100, function ($items) use ($disk, &$created, &$skipped, &$failed): void {
                foreach ($items as $media) {
                    $thumbPath = $media->thumb_path ?: Media::thumbnailPathFor($media->path);

                    if (! $this->option('force') && $disk->exists($thumbPath)) {
                        if ($media->thumb_path !== $thumbPath) {
                            $media->update(['thumb_path' => $thumbPath]);
                        }

                        $skipped++;

                        continue;
                    }

                    if (! $disk->exists($media->path)) {
                        $this->warn("Skipped missing file: {$media->path}");
                        $skipped++;

                        continue;
                    }

                    try {
                        $thumbnail = ImageManager::usingDriver(Driver::class)->decode($disk->path($media->path));
                        $thumbnail->scaleDown(self::THUMB_DIMENSION);
                        $thumbnail->save($disk->path($thumbPath));

                        $media->update(['thumb_path' => $thumbPath]);
                        $created++;
                    } catch (Throwable $exception) {
                        $this->error("Failed {$media->path}: {$exception->getMessage()}");
                        $failed++;
                    }
                }
            });

        $this->info("Thumbnails created: {$created}");
        $this->info("Thumbnails skipped: {$skipped}");
        $this->info("Thumbnails failed: {$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
