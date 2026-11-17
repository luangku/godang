<?php

namespace App\Http\Controllers;

use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StorageFileController extends Controller
{
    public function __invoke(Request $request, string $path)
    {
        abort_unless(Media::isServablePath($path), 404);
        abort_unless(Storage::disk('public')->exists($path), 404);

        $headers = [
            'Cache-Control' => 'public, max-age=31536000, immutable',
            'X-Content-Type-Options' => 'nosniff',
        ];

        if (str_starts_with($path, 'files/')) {
            $headers['Content-Disposition'] = 'inline; filename="'.basename($path).'"';
        }

        return Storage::disk('public')->response($path, headers: $headers);
    }
}
