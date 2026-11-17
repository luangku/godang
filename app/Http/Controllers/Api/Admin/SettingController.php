<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SettingController extends Controller
{
    /**
     * Keys that may be updated through the admin API.
     *
     * @var array<int, string>
     */
    private const BOOLEAN_KEYS = [
        'comment_moderation',
        'comment_notification',
    ];

    /**
     * Keys that must contain a valid URL.
     *
     * @var array<int, string>
     */
    private const URL_KEYS = [
        'github_url',
        'twitter_url',
        'linkedin_url',
    ];

    /**
     * Maximum length for text settings that differ from the default.
     *
     * @var array<string, int>
     */
    private const MAX_LENGTHS = [
        'site_title' => 150,
        'site_logo' => 2048,
        'site_logo_dark' => 2048,
        'site_logo_alt' => 255,
        'site_description' => 500,
        'tagline' => 255,
        'default_meta_title' => 255,
        'default_meta_description' => 300,
        'author_name' => 150,
        'author_role' => 150,
        'about_bio' => 5000,
        'about_topics_heading' => 150,
        'about_topics' => 2000,
        'about_contact_heading' => 150,
        'about_contact' => 1000,
        'about_meta_description' => 300,
    ];

    /**
     * Get all settings.
     */
    public function index(): JsonResponse
    {
        $values = Setting::allValues();
        $values['author_name'] = Setting::authorName();

        return response()->json($values);
    }

    /**
     * Update settings (batch). Unknown keys are rejected.
     */
    public function update(Request $request): JsonResponse
    {
        $request->validate([
            'settings' => ['required', 'array'],
        ]);

        /** @var array<string, mixed> $settings */
        $settings = $request->input('settings', []);

        $unknown = array_diff(array_keys($settings), array_keys(Setting::DEFAULTS));

        if ($unknown !== []) {
            throw ValidationException::withMessages([
                'settings' => 'Kunci pengaturan tidak dikenal: '.implode(', ', $unknown).'.',
            ]);
        }

        $rules = ['settings' => ['required', 'array']];

        foreach (array_keys($settings) as $key) {
            $rules["settings.{$key}"] = $this->rulesFor($key);
        }

        $validated = $request->validate($rules);

        foreach ($validated['settings'] as $key => $value) {
            if ($key === 'author_name') {
                continue;
            }

            Setting::setValue($key, is_string($value) ? trim($value) : $value);
        }

        return response()->json(['message' => 'Pengaturan berhasil diperbarui.']);
    }

    /**
     * Build validation rules for a single setting key.
     *
     * @return array<int, string>
     */
    private function rulesFor(string $key): array
    {
        if (in_array($key, self::BOOLEAN_KEYS, true)) {
            return ['nullable', 'in:true,false'];
        }

        $rules = ['nullable', 'string'];

        if (in_array($key, self::URL_KEYS, true)) {
            $rules[] = 'url';
        }

        $rules[] = 'max:'.(self::MAX_LENGTHS[$key] ?? 255);

        return $rules;
    }
}
