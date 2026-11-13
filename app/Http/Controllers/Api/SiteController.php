<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

class SiteController extends Controller
{
    /**
     * Get public site settings (for admin login branding).
     */
    public function settings(): JsonResponse
    {
        $settings = Setting::allValues();

        return response()->json([
            'site_title' => $settings['site_title'] ?? null,
            'site_logo' => $settings['site_logo'] ?? null,
            'site_logo_dark' => $settings['site_logo_dark'] ?? null,
            'site_logo_alt' => $settings['site_logo_alt'] ?? null,
        ]);
    }
}
