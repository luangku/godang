<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'avatar', 'avatar_alt', 'bio', 'is_admin', 'is_primary'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    use CanResetPassword;
    use HasApiTokens;

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = ['avatar_url'];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_primary' => 'boolean',
        ];
    }

    /**
     * Absolute URL for the user's avatar.
     */
    protected function avatarUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => Media::urlFor($this->avatar));
    }

    /**
     * Posts authored by this user.
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    /**
     * Comments authored by this user.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }
}
