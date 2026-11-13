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
use Illuminate\Support\Facades\Storage;

#[Fillable(['path', 'type', 'original_name', 'thumb_path', 'folder', 'media_category_id', 'alt_text', 'mime', 'size', 'width', 'height'])]
class Media extends Model
{
    /** @use HasFactory<MediaFactory> */
    use HasFactory, SoftDeletes;

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

        $path = ltrim($path, '/');

        return $path === '' ? null : $path;
    }
}
