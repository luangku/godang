<?php

namespace App\Models;

use Database\Factories\MediaCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name'])]
class MediaCategory extends Model
{
    /** @use HasFactory<MediaCategoryFactory> */
    use HasFactory;

    /**
     * Media assigned to this category.
     */
    public function media(): HasMany
    {
        return $this->hasMany(Media::class);
    }
}
