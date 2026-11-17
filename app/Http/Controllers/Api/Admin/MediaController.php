<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Media;
use App\Models\MediaCategory;
use App\Models\Post;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Exceptions\ImageException;
use Intervention\Image\ImageManager;

class MediaController extends Controller
{
    /**
     * Maximum width/height (in pixels) an uploaded image is scaled down to.
     */
    private const MAX_DIMENSION = 1600;

    /**
     * Maximum width/height (in pixels) for generated thumbnails.
     */
    private const THUMB_DIMENSION = 480;

    /**
     * Maximum upload size for non-image files, in kilobytes (10 MB).
     */
    private const FILE_MAX_KB = 10240;

    /**
     * Allowed non-image extensions mapped to the MIME types they may use.
     *
     * Anything outside this list (PHP, HTML, SVG, JS, executables, etc.) is
     * rejected even when the client sends a convincing MIME type.
     *
     * @var array<string, array<int, string>>
     */
    private const FILE_MIMES = [
        'pdf' => ['application/pdf'],
        'doc' => ['application/msword', 'application/vnd.ms-office', 'application/octet-stream'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/octet-stream'],
        'xls' => ['application/vnd.ms-excel', 'application/vnd.ms-office', 'application/octet-stream'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip', 'application/octet-stream'],
        'ppt' => ['application/vnd.ms-powerpoint', 'application/vnd.ms-office', 'application/octet-stream'],
        'pptx' => ['application/vnd.openxmlformats-officedocument.presentationml.presentation', 'application/zip', 'application/octet-stream'],
        'txt' => ['text/plain', 'text/csv', 'application/csv'],
        'csv' => ['text/csv', 'text/plain', 'application/csv'],
        'zip' => ['application/zip', 'application/x-zip-compressed', 'application/octet-stream'],
    ];

    /**
     * Folders that uploaded media may be stored in.
     *
     * @var array<int, string>
     */
    private const FOLDERS = ['uploads', 'cover', 'avatar', 'files'];

    /**
     * Pseudo-folder for rows stored in an unknown/legacy folder.
     */
    private const FOLDER_OTHER = 'other';

    /**
     * Pseudo-category for media without a custom category.
     */
    private const CATEGORY_UNCATEGORIZED = 'none';

    /**
     * Sort options accepted by the media index.
     *
     * @var array<int, string>
     */
    private const SORTS = ['newest', 'oldest', 'name', 'name_desc', 'largest', 'smallest'];

    /**
     * List media with search, folder, category, usage, sort, trash, and pagination filters.
     */
    public function index(Request $request): JsonResponse
    {
        $search = is_string($request->query('q')) ? trim($request->query('q')) : '';
        $type = $request->query('type') === 'file' ? 'file' : 'image';
        $folder = is_string($request->query('folder')) ? $request->query('folder') : '';
        $category = is_string($request->query('category')) ? $request->query('category') : '';
        $sort = is_string($request->query('sort')) ? $request->query('sort') : '';
        $onlyTrashed = $request->boolean('trashed');
        $onlyUnused = $request->boolean('unused');

        $baseQuery = Media::query()->where('type', $type)->with('category:id,name');

        if ($onlyTrashed) {
            $baseQuery->onlyTrashed();
        }

        if ($search !== '') {
            $baseQuery->where(function (Builder $query) use ($search) {
                $query->where('alt_text', 'like', "%{$search}%")
                    ->orWhere('original_name', 'like', "%{$search}%")
                    ->orWhere('path', 'like', "%{$search}%");
            });
        }

        if ($onlyUnused) {
            $this->applyUnusedFilter($baseQuery);
        }

        $folderBase = clone $baseQuery;
        $this->applyCategoryFilter($folderBase, $category);

        $categoryBase = clone $baseQuery;
        $this->applyFolderFilter($categoryBase, $folder);

        $query = clone $baseQuery;
        $this->applyFolderFilter($query, $folder);
        $this->applyCategoryFilter($query, $category);

        $query = $this->applySort($query, $sort);
        $media = $query->paginate($this->perPage($request));
        $this->attachUsage($media);

        $categoryCounts = $this->categoryCounts($categoryBase);
        $unusedQuery = clone $baseQuery;

        if (! $onlyUnused) {
            $this->applyUnusedFilter($unusedQuery);
        }

        return response()->json([
            ...$media->toArray(),
            'folders' => $this->folderCounts($folderBase),
            'categories' => $categoryCounts['items'],
            'uncategorized_count' => $categoryCounts['uncategorized'],
            'unused_count' => $unusedQuery->count(),
            'trashed' => $onlyTrashed,
            'type' => $type,
        ]);
    }

    /**
     * Store an uploaded image or non-image file.
     */
    public function store(Request $request): JsonResponse
    {
        return $request->input('type') === 'file'
            ? $this->storeFile($request)
            : $this->storeImage($request);
    }

    /**
     * Upload an image: scale down to max 1600px, convert to WebP, and create a thumbnail.
     */
    private function storeImage(Request $request): JsonResponse
    {
        $supportedMimes = $this->supportedImageMimes();

        $request->validate([
            'file' => ['required', 'image', 'mimes:'.implode(',', $supportedMimes), 'max:5120'], // 5MB max
            'alt_text' => ['nullable', 'string', 'max:255'],
            'folder' => ['nullable', 'string', 'in:'.implode(',', self::FOLDERS)],
            'category_id' => ['nullable', 'integer', 'exists:media_categories,id'],
        ], [
            'file.mimes' => 'Format gambar tidak didukung server. Gunakan: '.strtoupper(implode(', ', $supportedMimes)).'.',
        ]);

        $file = $request->file('file');
        $folder = $request->input('folder', 'uploads');
        $uuid = (string) Str::uuid();
        $relativePath = "{$folder}/{$uuid}.webp";
        $thumbRelativePath = "{$folder}/{$uuid}-thumb.webp";
        Storage::disk('public')->makeDirectory($folder);
        $absolutePath = Storage::disk('public')->path($relativePath);
        $thumbAbsolutePath = Storage::disk('public')->path($thumbRelativePath);
        $sourcePath = $file->getRealPath();

        if (! is_string($sourcePath) || $sourcePath === '') {
            return response()->json([
                'message' => 'File upload tidak valid. Silakan unggah ulang.',
            ], 422);
        }

        // Read the uploaded bytes directly instead of handing the temp path to
        // Intervention. Shared hosts often point upload_tmp_dir at a directory
        // (e.g. /home/uploads) that is searchable but not readable, so the
        // library's is_readable(dirname()) check fails even though the file
        // itself can be opened.
        $contents = @file_get_contents($sourcePath);

        if ($contents === false || $contents === '') {
            Log::warning('Media upload could not be read.', [
                'original_name' => $file->getClientOriginalName(),
                'client_mime' => $file->getClientMimeType(),
                'source' => $sourcePath,
                'readable' => is_readable($sourcePath),
            ]);

            return response()->json([
                'message' => 'File upload tidak dapat dibaca server. Silakan unggah ulang.',
            ], 422);
        }

        try {
            $manager = ImageManager::usingDriver(Driver::class);

            $image = $manager->decodeBinary($contents);
            $image->scaleDown(self::MAX_DIMENSION);
            $image->save($absolutePath);

            // Decode the original bytes again for the thumbnail so the result
            // never depends on reading the freshly written file back from disk.
            $thumbnail = $manager->decodeBinary($contents);
            $thumbnail->scaleDown(self::THUMB_DIMENSION);
            $thumbnail->save($thumbAbsolutePath);
        } catch (ImageException $exception) {
            Log::warning('Media upload could not be processed.', [
                'original_name' => $file->getClientOriginalName(),
                'client_mime' => $file->getClientMimeType(),
                'reason' => $exception->getMessage(),
            ]);

            Storage::disk('public')->delete([$relativePath, $thumbRelativePath]);

            return response()->json([
                'message' => 'Gambar tidak dapat diproses server. Format yang didukung: '
                    .strtoupper(implode(', ', $supportedMimes)).'.',
            ], 422);
        }

        $media = Media::create([
            'path' => $relativePath,
            'type' => 'image',
            'original_name' => $file->getClientOriginalName(),
            'thumb_path' => $thumbRelativePath,
            'folder' => $folder,
            'media_category_id' => $request->input('category_id'),
            'alt_text' => $request->alt_text ?? $file->getClientOriginalName(),
            'mime' => 'image/webp',
            'size' => Storage::disk('public')->size($relativePath),
            'width' => $image->width(),
            'height' => $image->height(),
        ]);

        return response()->json([
            'message' => 'Berkas berhasil diunggah.',
            'media' => $media->load('category:id,name'),
            'url' => $media->url,
        ], 201);
    }

    /**
     * Upload a non-image file after validating its extension and MIME type.
     */
    private function storeFile(Request $request): JsonResponse
    {
        $extensions = array_keys(self::FILE_MIMES);
        $allowedMimes = array_values(array_unique(array_merge(...array_values(self::FILE_MIMES))));

        $request->validate([
            'file' => [
                'required',
                'file',
                'max:'.self::FILE_MAX_KB,
                'mimes:'.implode(',', $extensions),
                'mimetypes:'.implode(',', $allowedMimes),
            ],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', 'exists:media_categories,id'],
        ], [
            'file.mimes' => 'Format file tidak didukung. Gunakan: '.strtoupper(implode(', ', $extensions)).'.',
            'file.mimetypes' => 'Tipe MIME file tidak diizinkan server.',
            'file.max' => 'Ukuran file maksimal 10 MB.',
        ]);

        $file = $request->file('file');
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $mime = strtolower((string) $file->getMimeType());
        $clientMime = strtolower((string) $file->getClientMimeType());
        $extensionMimes = self::FILE_MIMES[$extension] ?? [];

        if ($extensionMimes === []
            || (! in_array($mime, $extensionMimes, true) && ! in_array($clientMime, $extensionMimes, true))) {
            throw ValidationException::withMessages([
                'file' => 'Format file tidak didukung atau tipe MIME tidak cocok dengan ekstensinya.',
            ]);
        }

        $folder = 'files';
        $relativePath = $folder.'/'.Str::uuid().'.'.$extension;
        Storage::disk('public')->makeDirectory($folder);
        Storage::disk('public')->putFileAs($folder, $file, basename($relativePath));

        $media = Media::create([
            'path' => $relativePath,
            'type' => 'file',
            'original_name' => $file->getClientOriginalName(),
            'thumb_path' => null,
            'folder' => $folder,
            'media_category_id' => $request->input('category_id'),
            'alt_text' => $request->alt_text ?? $file->getClientOriginalName(),
            'mime' => $mime !== '' ? $mime : $clientMime,
            'size' => Storage::disk('public')->size($relativePath),
            'width' => null,
            'height' => null,
        ]);

        return response()->json([
            'message' => 'Berkas berhasil diunggah.',
            'media' => $media->load('category:id,name'),
            'url' => $media->url,
        ], 201);
    }

    /**
     * Update editable media metadata (alt text and category).
     */
    public function update(Request $request, Media $media): JsonResponse
    {
        $request->validate([
            'alt_text' => ['nullable', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', 'exists:media_categories,id'],
        ]);

        $changes = [];

        if ($request->has('alt_text')) {
            $changes['alt_text'] = $request->input('alt_text');
        }

        if ($request->has('category_id')) {
            $changes['media_category_id'] = $request->input('category_id');
        }

        if ($changes !== []) {
            $media->update($changes);
        }

        return response()->json([
            'message' => 'Media diperbarui.',
            'media' => $media->fresh()->load('category:id,name'),
        ]);
    }

    /**
     * Move media to trash. The file stays on disk until permanently deleted.
     */
    public function destroy(Media $media): JsonResponse
    {
        $usage = $this->usageForPaths([$media->path])[$media->path];

        $media->delete();

        return response()->json([
            'message' => $usage['count'] > 0
                ? 'Media dipindahkan ke sampah. File masih dipakai, jadi jangan hapus permanen sebelum referensinya dilepas.'
                : 'Media dipindahkan ke sampah.',
            'usage' => $usage,
        ]);
    }

    /**
     * Restore media from trash.
     */
    public function restore(Media $media): JsonResponse
    {
        $media->restore();

        return response()->json([
            'message' => 'Media dipulihkan.',
            'media' => $media->load('category:id,name'),
        ]);
    }

    /**
     * Permanently delete media and its files.
     */
    public function forceDelete(Media $media): JsonResponse
    {
        $usage = $this->usageForPaths([$media->path])[$media->path];

        if ($usage['count'] > 0) {
            return response()->json([
                'message' => 'Media masih dipakai. Lepaskan referensinya sebelum menghapus permanen.',
                'usage' => $usage,
            ], 409);
        }

        $this->deleteFiles($media);
        $media->forceDelete();

        return response()->json(['message' => 'Media dihapus permanen.']);
    }

    /**
     * Move multiple media items to trash.
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $count = Media::whereIn('id', $validated['ids'])->count();
        Media::whereIn('id', $validated['ids'])->delete();

        return response()->json(['message' => "{$count} media dipindahkan ke sampah."]);
    }

    /**
     * Restore multiple media items from trash.
     */
    public function bulkRestore(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $count = Media::onlyTrashed()->whereIn('id', $validated['ids'])->restore();

        return response()->json(['message' => "{$count} media dipulihkan."]);
    }

    /**
     * Permanently delete multiple media items. Used items block the whole batch.
     */
    public function bulkForceDelete(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $items = Media::onlyTrashed()->whereIn('id', $validated['ids'])->get();
        $usage = $this->usageForPaths($items->pluck('path')->all());

        $blocked = $items
            ->filter(fn (Media $media): bool => ($usage[$media->path]['count'] ?? 0) > 0)
            ->map(fn (Media $media): array => [
                'id' => $media->id,
                'alt_text' => $media->alt_text,
                'path' => $media->path,
                'usage_count' => $usage[$media->path]['count'] ?? 0,
                'usage_labels' => $usage[$media->path]['labels'] ?? [],
            ])
            ->values();

        if ($blocked->isNotEmpty()) {
            return response()->json([
                'message' => 'Ada media yang masih dipakai. Lepaskan referensinya sebelum menghapus permanen.',
                'blocked' => $blocked,
            ], 409);
        }

        $items->each(function (Media $media): void {
            $this->deleteFiles($media);
            $media->forceDelete();
        });

        return response()->json(['message' => "{$items->count()} media dihapus permanen."]);
    }

    /**
     * Apply a folder filter, including the pseudo-folder for legacy paths.
     */
    private function applyFolderFilter(Builder $query, string $folder): void
    {
        if ($folder === self::FOLDER_OTHER) {
            $query->whereNotIn('folder', self::FOLDERS);

            return;
        }

        if (in_array($folder, self::FOLDERS, true)) {
            $query->where('folder', $folder);
        }
    }

    /**
     * Apply a custom category filter, including the pseudo-category for uncategorized media.
     */
    private function applyCategoryFilter(Builder $query, string $category): void
    {
        if ($category === self::CATEGORY_UNCATEGORIZED) {
            $query->whereNull('media_category_id');

            return;
        }

        if (ctype_digit($category)) {
            $query->where('media_category_id', (int) $category);
        }
    }

    /**
     * Apply a whitelisted sort order to the media query.
     */
    private function applySort(Builder $query, string $sort): Builder
    {
        if (! in_array($sort, self::SORTS, true)) {
            $sort = 'newest';
        }

        return match ($sort) {
            'oldest' => $query->oldest('id'),
            'name' => $query->orderBy('alt_text')->orderBy('id'),
            'name_desc' => $query->orderByDesc('alt_text')->orderByDesc('id'),
            'largest' => $query->orderByDesc('size')->orderByDesc('id'),
            'smallest' => $query->orderBy('size')->orderByDesc('id'),
            default => $query->latest('id'),
        };
    }

    /**
     * Count media per folder, respecting the current search/trash filters.
     *
     * @return array<string, int>
     */
    private function folderCounts(Builder $query): array
    {
        $stored = (clone $query)
            ->selectRaw('folder, count(*) as aggregate')
            ->groupBy('folder')
            ->pluck('aggregate', 'folder')
            ->map(fn (mixed $count): int => (int) $count)
            ->all();

        $counts = [
            'all' => array_sum($stored),
            self::FOLDER_OTHER => array_sum(array_diff_key($stored, array_flip(self::FOLDERS))),
        ];

        foreach (self::FOLDERS as $folder) {
            $counts[$folder] = $stored[$folder] ?? 0;
        }

        return $counts;
    }

    /**
     * Count media per custom category, respecting the current search/folder filters.
     *
     * @return array{items: array<int, array{id: int, name: string, count: int}>, uncategorized: int}
     */
    private function categoryCounts(Builder $query): array
    {
        $stored = (clone $query)
            ->selectRaw('media_category_id, count(*) as aggregate')
            ->groupBy('media_category_id')
            ->pluck('aggregate', 'media_category_id')
            ->map(fn (mixed $count): int => (int) $count)
            ->all();

        $items = MediaCategory::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (MediaCategory $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'count' => $stored[$category->id] ?? 0,
            ])
            ->all();

        return [
            'items' => $items,
            'uncategorized' => (clone $query)->whereNull('media_category_id')->count(),
        ];
    }

    /**
     * Add usage metadata to each item on the current page.
     */
    private function attachUsage(LengthAwarePaginator $media): void
    {
        $usage = $this->usageForPaths($media->getCollection()->pluck('path')->all());

        $media->getCollection()->each(function (Media $item) use ($usage): void {
            $info = $usage[$item->path] ?? ['count' => 0, 'labels' => []];

            $item->setAttribute('usage_count', $info['count']);
            $item->setAttribute('usage_labels', $info['labels']);
        });
    }

    /**
     * Cached usage map, keyed by the normalized media path.
     *
     * @var array<string, array{count: int, labels: array<int, string>}>|null
     */
    private ?array $usageCache = null;

    /**
     * Find posts/settings that reference the given media paths.
     *
     * Only exact path matches are counted, so "uploads/a.webp" is not reported
     * as used when an article merely references "uploads/a.webp.bak".
     *
     * @param  array<int, string|null>  $paths
     * @return array<string, array{count: int, labels: array<int, string>}>
     */
    private function usageForPaths(array $paths): array
    {
        $paths = array_values(array_unique(array_filter($paths)));

        $usage = [];

        foreach ($paths as $path) {
            $usage[$path] = ['count' => 0, 'labels' => []];
        }

        if ($paths === []) {
            return $usage;
        }

        $referenced = $this->referencedUsage();

        foreach ($paths as $path) {
            $normalized = Media::diskPath($path) ?? $path;

            $usage[$path] = $referenced[$normalized] ?? ['count' => 0, 'labels' => []];
        }

        return $usage;
    }

    /**
     * Build the exact usage map for every media path referenced by content.
     *
     * @return array<string, array{count: int, labels: array<int, string>}>
     */
    private function referencedUsage(): array
    {
        if ($this->usageCache !== null) {
            return $this->usageCache;
        }

        $usage = [];

        Post::withTrashed()
            ->select(['id', 'title', 'cover_image', 'og_image', 'content'])
            ->chunkById(200, function ($posts) use (&$usage): void {
                foreach ($posts as $post) {
                    $cover = Media::diskPath($post->cover_image);

                    if ($cover !== null) {
                        $this->addUsage($usage, $cover, "Cover artikel: {$post->title}");
                    }

                    $og = Media::diskPath($post->og_image);

                    if ($og !== null) {
                        $this->addUsage($usage, $og, "OG artikel: {$post->title}");
                    }

                    foreach ($this->contentMediaPaths($post->content) as $path) {
                        $this->addUsage($usage, $path, "Konten artikel: {$post->title}");
                    }
                }
            });

        User::query()
            ->whereNotNull('avatar')
            ->select(['id', 'name', 'avatar'])
            ->chunkById(200, function ($users) use (&$usage): void {
                foreach ($users as $user) {
                    $avatar = Media::diskPath($user->avatar);

                    if ($avatar !== null) {
                        $this->addUsage($usage, $avatar, "Avatar: {$user->name}");
                    }
                }
            });

        $settingReferences = [
            'site_logo' => 'Logo situs',
            'site_logo_dark' => 'Logo situs (mode gelap)',
        ];

        foreach ($settingReferences as $key => $label) {
            $path = Media::diskPath(Setting::getValue($key));

            if ($path !== null) {
                $this->addUsage($usage, $path, $label);
            }
        }

        return $this->usageCache = $usage;
    }

    /**
     * Extract every media path referenced from an article body.
     *
     * Absolute /storage/ URLs and legacy "storage/" prefixes are normalized
     * before matching so the result is always a public disk path.
     *
     * @return array<int, string>
     */
    private function contentMediaPaths(?string $content): array
    {
        if ($content === null || trim($content) === '') {
            return [];
        }

        $normalized = preg_replace('#(?:https?://[^"\'\s<>()]+?)?/storage/#i', '/', $content) ?? $content;

        preg_match_all('#(?<![\w.-])(?:uploads|cover|avatar|files)/[\w.-]+#i', $normalized, $matches);

        $paths = array_map(
            fn (string $path): string => rtrim($path, '.,;:'),
            $matches[0]
        );

        return array_values(array_unique(array_filter($paths)));
    }

    /**
     * Record one usage of a media path while keeping the label list short.
     *
     * @param  array<string, array{count: int, labels: array<int, string>}>  $usage
     */
    private function addUsage(array &$usage, string $path, string $label): void
    {
        $usage[$path] ??= ['count' => 0, 'labels' => []];

        $usage[$path]['count']++;

        if (count($usage[$path]['labels']) < 5 && ! in_array($label, $usage[$path]['labels'], true)) {
            $usage[$path]['labels'][] = $label;
        }
    }

    /**
     * Filter out media that is still referenced by posts, admin avatars, or the site logo.
     */
    private function applyUnusedFilter(Builder $query): void
    {
        $query->whereNotIn('path', $this->usedPathVariants());
    }

    /**
     * Every stored representation of a referenced media path.
     *
     * Media paths are normalized, but legacy rows may still carry a "storage/"
     * prefix or an absolute /storage/ URL.
     *
     * @return array<int, string>
     */
    private function usedPathVariants(): array
    {
        $variants = [];

        foreach (array_keys($this->referencedUsage()) as $path) {
            $variants[] = $path;
            $variants[] = 'storage/'.$path;
            $variants[] = '/storage/'.$path;
            $variants[] = rtrim((string) config('app.url'), '/').'/storage/'.$path;
        }

        return array_values(array_unique($variants));
    }

    /**
     * Image formats the GD driver can actually decode on this server.
     *
     * Shared hosts often compile GD without AVIF (and sometimes WebP) support,
     * so the validation list is derived from the installed GD capabilities.
     *
     * @return array<int, string>
     */
    private function supportedImageMimes(): array
    {
        if (! extension_loaded('gd') || ! function_exists('imagetypes')) {
            return ['jpg', 'jpeg', 'png'];
        }

        $types = imagetypes();
        $mimes = [];

        $formats = [
            IMG_JPEG => ['jpg', 'jpeg'],
            IMG_PNG => ['png'],
            IMG_GIF => ['gif'],
            IMG_WEBP => ['webp'],
            IMG_BMP => ['bmp'],
            IMG_AVIF => ['avif'],
        ];

        foreach ($formats as $flag => $extensions) {
            if (($types & $flag) !== 0) {
                $mimes = [...$mimes, ...$extensions];
            }
        }

        return $mimes === [] ? ['jpg', 'jpeg', 'png'] : $mimes;
    }

    /**
     * Delete the original file and its thumbnail from the public disk.
     */
    private function deleteFiles(Media $media): void
    {
        foreach ([$media->path, $media->thumb_path] as $value) {
            $path = Media::diskPath($value);

            if ($path !== null && ! str_contains($path, '..')) {
                Storage::disk('public')->delete($path);
            }
        }
    }

    /**
     * Clamp the requested page size between 1 and 50.
     */
    private function perPage(Request $request): int
    {
        return max(1, min(50, $request->integer('per_page', 20)));
    }
}
