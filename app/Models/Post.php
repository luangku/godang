<?php

namespace App\Models;

use Database\Factories\PostFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

#[Fillable([
    'user_id', 'category_id',
    'title', 'slug', 'excerpt', 'content', 'cover_image', 'og_image',
    'status', 'published_at', 'meta_title', 'meta_description',
    'reading_time', 'views_count',
])]
class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasFactory, SoftDeletes;

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = ['cover_url', 'og_url'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'views_count' => 'integer',
            'reading_time' => 'integer',
        ];
    }

    /**
     * Boot the model — auto-generate slug and reading_time.
     */
    protected static function booted(): void
    {
        static::saving(function (Post $post) {
            if (empty($post->slug) && ! empty($post->title)) {
                $post->slug = Str::slug($post->title);
            }

            if (! empty($post->content)) {
                $wordCount = str_word_count(strip_tags($post->content));
                $post->reading_time = max(1, (int) ceil($wordCount / 200));
            }
        });
    }

    /**
     * The author of this post.
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * The category of this post.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Tags attached to this post.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    /**
     * Comments on this post.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Scope: only published posts.
     */
    #[Scope]
    protected function published(Builder $query): Builder
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    /**
     * Resolve a media path/URL to an absolute URL.
     */
    protected function resolveMediaUrl(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        $path = Media::diskPath($value);

        if ($path === null) {
            return Media::isExternalUrl($value) ? $value : null;
        }

        return Storage::disk('public')->url($path);
    }

    /**
     * Absolute URL for the cover image.
     */
    public function coverImageUrl(): ?string
    {
        return $this->resolveMediaUrl($this->cover_image);
    }

    /**
     * Absolute URL for the og image.
     */
    public function ogImageUrl(): ?string
    {
        return $this->resolveMediaUrl($this->og_image);
    }

    /**
     * Appended cover URL attribute.
     */
    protected function coverUrl(): Attribute
    {
        return Attribute::get(fn () => $this->coverImageUrl());
    }

    /**
     * Appended og URL attribute.
     */
    protected function ogUrl(): Attribute
    {
        return Attribute::get(fn () => $this->ogImageUrl());
    }

    /**
     * Determine whether the post is publicly visible right now.
     */
    public function isPublished(): bool
    {
        return $this->status === 'published'
            && $this->published_at !== null
            && $this->published_at->lte(now());
    }
}
