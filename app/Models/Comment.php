<?php

namespace App\Models;

use Database\Factories\CommentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'post_id', 'parent_id', 'reply_to_name', 'name', 'email', 'body',
    'status', 'is_author', 'notify_replies', 'ip_hash', 'user_agent',
])]
#[Hidden(['email', 'ip_hash', 'user_agent'])]
class Comment extends Model
{
    /** @use HasFactory<CommentFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_author' => 'boolean',
            'notify_replies' => 'boolean',
        ];
    }

    /**
     * Display the configured author name for comments written by the author.
     */
    protected function name(): Attribute
    {
        return Attribute::get(
            fn (?string $value) => $this->is_author
                ? Setting::authorName($value)
                : $value
        );
    }

    /**
     * Store the comment body as plain text so no markup reaches the page.
     */
    protected function body(): Attribute
    {
        return Attribute::set(function (?string $value): ?string {
            if ($value === null) {
                return null;
            }

            $text = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', '', $value) ?? $value;
            $text = str_replace(["\r\n", "\r"], "\n", strip_tags($text));
            $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

            return trim($text);
        });
    }

    /**
     * The post this comment belongs to.
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * The parent comment (for replies).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * Replies to this comment.
     */
    public function replies(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    /**
     * Scope: only approved comments.
     */
    #[Scope]
    protected function approved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope: only pending comments.
     */
    #[Scope]
    protected function pending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: only root comments (no parent).
     */
    #[Scope]
    protected function rootComments(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }
}
