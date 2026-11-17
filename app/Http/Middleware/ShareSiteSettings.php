<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ShareSiteSettings
{
    /**
     * Share site settings with every public view.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $settings = Setting::allValues();
        $author = Setting::primaryAdmin();
        $authorName = trim((string) ($author?->name ?? ''))
            ?: (trim((string) ($settings['author_name'] ?? '')) ?: 'Godang');

        $site = [
            'site_title' => trim((string) ($settings['site_title'] ?? '')) ?: 'Godang',
            'site_logo' => $settings['site_logo'] ?? null,
            'site_logo_dark' => $settings['site_logo_dark'] ?? null,
            'site_logo_alt' => $settings['site_logo_alt'] ?? null,
            'site_description' => $settings['site_description'] ?? null,
            'tagline' => $settings['tagline'] ?? null,
            'default_meta_title' => $settings['default_meta_title'] ?? null,
            'default_meta_description' => $settings['default_meta_description'] ?? null,
            'github_url' => $settings['github_url'] ?? null,
            'twitter_url' => $settings['twitter_url'] ?? null,
            'linkedin_url' => $settings['linkedin_url'] ?? null,
            'author_avatar' => $author?->avatar,
            'author_avatar_alt' => $author?->avatar_alt,
            'author_avatar_url' => $author?->avatar_url,
            'author_name' => $authorName,
            'author_initial' => Str::upper(Str::substr($authorName, 0, 1)),
            'author_role' => $settings['author_role'] ?? null,
            'about_bio' => $settings['about_bio'] ?? null,
            'about_topics_heading' => $settings['about_topics_heading'] ?? null,
            'about_topics' => $settings['about_topics'] ?? null,
            'about_contact_heading' => $settings['about_contact_heading'] ?? null,
            'about_contact' => $settings['about_contact'] ?? null,
            'about_meta_description' => $settings['about_meta_description'] ?? null,
        ];

        view()->share('site', $site);

        return $next($request);
    }
}
