<?php

namespace App\Models;

use Database\Factories\MediaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Throwable;

#[Fillable(['path', 'type', 'original_name', 'thumb_path', 'folder', 'media_category_id', 'alt_text', 'mime', 'size', 'width', 'height'])]
class Media extends Model
{
    /** @use HasFactory<MediaFactory> */
    use HasFactory, SoftDeletes;

    /**
     * Image extensions the scanner may register from disk.
     *
     * @var array<int, string>
     */
    private const SCAN_IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'avif'];

    /**
     * Downloadable file extensions the scanner may register from disk.
     *
     * @var array<int, string>
     */
    private const SCAN_FILE_EXTENSIONS = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv', 'zip'];

    /**
     * Maximum width/height (in pixels) for thumbnails created while scanning.
     */
    private const SCAN_THUMB_DIMENSION = 480;

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = ['url', 'thumb_url', 'download_name'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
        ];
    }

    /**
     * Category assigned to this media item.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(MediaCategory::class, 'media_category_id');
    }

    /**
     * Limit the query to image media.
     */
    public function scopeImages(Builder $query): Builder
    {
        return $query->where('type', 'image');
    }

    /**
     * Limit the query to non-image files.
     */
    public function scopeFiles(Builder $query): Builder
    {
        return $query->where('type', 'file');
    }

    /**
     * Full URL to the media file.
     */
    protected function url(): Attribute
    {
        return Attribute::get(fn () => self::urlFor($this->path));
    }

    /**
     * URL to the thumbnail, falling back to the original file.
     */
    protected function thumbUrl(): Attribute
    {
        return Attribute::get(fn () => self::urlFor($this->thumb_path ?: $this->path));
    }

    /**
     * Human-readable filename used when the file is downloaded.
     */
    protected function downloadName(): Attribute
    {
        return Attribute::get(fn () => $this->original_name ?: basename((string) $this->path));
    }

    /**
     * Derive the thumbnail path for a stored media path.
     */
    public static function thumbnailPathFor(string $path): string
    {
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $base = $extension !== '' ? substr($path, 0, -strlen($extension) - 1) : $path;

        return $base.'-thumb.webp';
    }

    /**
     * Register files that exist on the public disk but have no media record yet.
     *
     * Files that were copied to storage during a migration often never make it
     * into the media table, which makes them invisible in the admin panel. This
     * scans the disk, skips thumbnails and unsafe extensions, and creates the
     * missing records.
     *
     * @return array{scanned: int, created: int, skipped: int, remaining: int, created_paths: array<int, string>}
     */
    public static function scanPublicDisk(int $limit = 50, bool $generateThumbnails = true): array
    {
        $disk = Storage::disk('public');
        $tracked = self::trackedDiskPaths();
        $candidates = [];

        foreach ($disk->allFiles() as $file) {
            $path = self::diskPath($file);

            if ($path === null || isset($tracked[$path]) || ! self::isScannablePath($path)) {
                continue;
            }

            $candidates[] = $path;
        }

        sort($candidates);

        $createdPaths = [];
        $skipped = 0;
        $processed = 0;

        foreach ($candidates as $path) {
            if (count($createdPaths) >= $limit) {
                break;
            }

            $processed++;

            if (self::registerScannedPath($path, $generateThumbnails) === null) {
                $skipped++;

                continue;
            }

            $createdPaths[] = $path;
        }

        return [
            'scanned' => count($candidates),
            'created' => count($createdPaths),
            'skipped' => $skipped,
            'remaining' => max(0, count($candidates) - $processed),
            'created_paths' => array_slice($createdPaths, 0, 20),
        ];
    }

    /**
     * Every normalized path that already has a media record, including trashed ones.
     *
     * @return array<string, true>
     */
    private static function trackedDiskPaths(): array
    {
        $paths = self::withTrashed()
            ->pluck('path')
            ->map(fn (?string $path): ?string => self::diskPath($path))
            ->filter()
            ->values()
            ->all();

        return array_fill_keys($paths, true);
    }

    /**
     * Determine whether a disk path is safe and relevant enough to register.
     */
    private static function isScannablePath(string $path): bool
    {
        if (preg_match('/-thumb\.[a-z0-9]+$/i', basename($path)) === 1) {
            return false;
        }

        return self::isServablePath($path);
    }

    /**
     * Determine whether a public disk path may be served by the storage route.
     *
     * Paths from older migrations may live in nested or legacy folders, so the
     * route accepts them as long as the path cannot escape the public disk and
     * the extension is one the media library knows how to display safely.
     */
    public static function isServablePath(?string $path): bool
    {
        if ($path === null || trim($path) === '') {
            return false;
        }

        $path = str_replace('\\', '/', trim($path));

        if (str_contains($path, '..') || str_starts_with($path, '/')) {
            return false;
        }

        if (preg_match('#[\x00-\x1F<>:"|?*\#%\\\\]#', $path) === 1) {
            return false;
        }

        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || str_starts_with($segment, '.')) {
                return false;
            }
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        return in_array($extension, self::SCAN_IMAGE_EXTENSIONS, true)
            || in_array($extension, self::SCAN_FILE_EXTENSIONS, true);
    }

    /**
     * Create a media record for one scanned disk path.
     */
    private static function registerScannedPath(string $path, bool $generateThumbnails): ?self
    {
        $disk = Storage::disk('public');

        if (! $disk->exists($path)) {
            return null;
        }

        $absolutePath = $disk->path($path);
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $isImage = in_array($extension, self::SCAN_IMAGE_EXTENSIONS, true);
        $mime = null;
        $width = null;
        $height = null;

        if ($isImage) {
            $dimensions = @getimagesize($absolutePath);

            if ($dimensions === false || ! isset($dimensions['mime'])) {
                return null;
            }

            $mime = $dimensions['mime'];
            $width = $dimensions[0] ?? null;
            $height = $dimensions[1] ?? null;
        } else {
            $detected = @mime_content_type($absolutePath);
            $mime = is_string($detected) ? $detected : null;
        }

        try {
            return self::create([
                'path' => $path,
                'type' => $isImage ? 'image' : 'file',
                'original_name' => basename($path),
                'thumb_path' => $isImage ? self::thumbnailForScannedPath($path, $generateThumbnails) : null,
                'folder' => self::folderFromPath($path),
                'media_category_id' => null,
                'alt_text' => self::altTextForScannedPath($path),
                'mime' => $mime,
                'size' => $disk->size($path),
                'width' => $width,
                'height' => $height,
            ]);
        } catch (Throwable $exception) {
            Log::warning('Media scan could not register file.', [
                'path' => $path,
                'reason' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Use an existing thumbnail, or generate one when allowed.
     */
    private static function thumbnailForScannedPath(string $path, bool $generate): ?string
    {
        $disk = Storage::disk('public');
        $thumbPath = self::thumbnailPathFor($path);

        if ($disk->exists($thumbPath)) {
            return $thumbPath;
        }

        if (! $generate) {
            return null;
        }

        try {
            $thumbnail = ImageManager::usingDriver(Driver::class)->decode($disk->path($path));
            $thumbnail->scaleDown(self::SCAN_THUMB_DIMENSION);
            $thumbnail->save($disk->path($thumbPath));

            return $thumbPath;
        } catch (Throwable $exception) {
            Log::warning('Media scan could not create thumbnail.', [
                'path' => $path,
                'reason' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Derive the top-level folder name from a disk path.
     */
    private static function folderFromPath(string $path): string
    {
        $directory = trim(str_replace('\\', '/', dirname($path)), '/');

        if ($directory === '' || $directory === '.') {
            return 'other';
        }

        return explode('/', $directory)[0];
    }

    /**
     * Build a readable alt text from a scanned file name.
     */
    private static function altTextForScannedPath(string $path): string
    {
        $name = trim((string) preg_replace('/[-_]+/', ' ', pathinfo($path, PATHINFO_FILENAME)));

        return $name === '' ? basename($path) : mb_substr($name, 0, 255);
    }

    /**
     * Resolve a stored media path or URL into an absolute URL.
     */
    public static function urlFor(?string $value): ?string
    {
        $path = self::diskPath($value);

        if ($path === null) {
            return self::isExternalUrl($value) ? $value : null;
        }

        return Storage::disk('public')->url($path);
    }

    /**
     * Resolve a stored value into a path relative to the public disk.
     *
     * Handles legacy values such as "storage/cover/file.webp" and absolute
     * URLs containing a /storage/ segment. Returns null for external URLs
     * or empty values.
     */
    public static function diskPath(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $value = trim($value);

        if (preg_match('#^https?://#i', $value)) {
            return self::storagePathFromUrl($value);
        }

        return self::normalizePath($value);
    }

    /**
     * Determine whether the stored value is an external (non-storage) URL.
     */
    public static function isExternalUrl(?string $value): bool
    {
        return is_string($value)
            && preg_match('#^https?://#i', $value) === 1
            && self::storagePathFromUrl(trim($value)) === null;
    }

    /**
     * Extract the public disk path from an absolute URL.
     */
    private static function storagePathFromUrl(string $url): ?string
    {
        $path = (string) parse_url($url, PHP_URL_PATH);

        if (! preg_match('#^/storage/(.+)$#i', $path, $matches)) {
            return null;
        }

        return self::normalizePath($matches[1]);
    }

    /**
     * Strip legacy prefixes from a path relative to the public disk.
     */
    private static function normalizePath(string $path): ?string
    {
        $path = ltrim($path, '/');

        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }

        if (str_starts_with($path, 'app/public/')) {
            $path = substr($path, strlen('app/public/'));
        }

        if (str_starts_with($path, 'public/')) {
            $path = substr($path, strlen('public/'));
        }

        $path = ltrim($path, '/');

        return $path === '' ? null : $path;
    }
}
