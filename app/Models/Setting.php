<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['key', 'value'])]
class Setting extends Model
{
    /**
     * Default values for every setting key.
     *
     * @var array<string, string|null>
     */
    public const DEFAULTS = [
        'site_title' => 'Godang',
        'site_logo' => null,
        'site_logo_dark' => null,
        'site_logo_alt' => null,
        'site_description' => 'Blog teknis pribadi — berbagi masalah dan solusi teknis.',
        'tagline' => 'Berbagi masalah & solusi teknis',
        'default_meta_title' => 'Godang — Blog Teknis Pribadi',
        'default_meta_description' => 'Blog teknis pribadi: sharing masalah dan solusi pemrograman.',
        'github_url' => 'https://github.com',
        'twitter_url' => 'https://twitter.com',
        'linkedin_url' => 'https://linkedin.com',
        'comment_moderation' => 'true',
        'comment_notification' => 'true',

        // Profil penulis / halaman About.
        'author_avatar' => null,
        'author_avatar_alt' => null,
        'author_name' => 'Godang',
        'author_role' => 'Developer & Penulis Teknis',
        'about_bio' => "Halo! Saya seorang developer yang senang mendokumentasikan masalah teknis yang pernah saya hadapi beserta solusinya. Blog ini dibuat agar catatan-catatan tersebut bisa bermanfaat untuk orang lain yang mungkin mengalami masalah serupa.\n\nSaya percaya bahwa berbagi pengetahuan adalah cara terbaik untuk belajar. Setiap masalah yang kita selesaikan bisa menjadi jalan pintas bagi orang lain.",
        'about_topics_heading' => 'Topik yang Dibahas',
        'about_topics' => "Troubleshooting Laravel & PHP\nPengembangan Web (Frontend & Backend)\nDevOps & Server\nDatabase & Performa\nTips & Trik Coding",
        'about_contact_heading' => 'Hubungi Saya',
        'about_contact' => 'Punya pertanyaan atau ingin berdiskusi? Silakan tinggalkan komentar di artikel terkait atau hubungi saya melalui media sosial.',
        'about_meta_description' => 'Tentang penulis blog ini.',
    ];

    /**
     * Get a setting value by key.
     */
    public static function getValue(string $key, ?string $default = null): ?string
    {
        return static::where('key', $key)->value('value') ?? $default;
    }

    /**
     * Get the configured public author name.
     */
    public static function authorName(?string $fallback = null): string
    {
        $name = trim((string) static::getValue('author_name', self::DEFAULTS['author_name']));

        return $name !== '' ? $name : ($fallback ?: (string) self::DEFAULTS['author_name']);
    }

    /**
     * Set a setting value by key.
     */
    public static function setValue(string $key, ?string $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
    }

    /**
     * Get every setting merged over the built-in defaults.
     *
     * @return array<string, string|null>
     */
    public static function allValues(): array
    {
        $stored = static::query()->pluck('value', 'key')->all();

        return array_merge(self::DEFAULTS, $stored);
    }
}
