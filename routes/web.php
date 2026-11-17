<?php

use App\Http\Controllers\PublicController;
use App\Http\Controllers\SeoController;
use App\Http\Controllers\StorageFileController;
use App\Http\Middleware\ShareSiteSettings;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
|
| Indonesian-only public-facing site served from the root URL.
|
*/

Route::middleware(ShareSiteSettings::class)->group(function () {
    Route::get('/', [PublicController::class, 'landing'])->name('home');
    Route::get('/blog', [PublicController::class, 'blogIndex'])->name('blog.index');
    Route::get('/blog/{slug}', [PublicController::class, 'blogShow'])->name('blog.show');
    Route::get('/about', [PublicController::class, 'about'])->name('about');
    Route::get('/category/{slug}', [PublicController::class, 'category'])->name('category.show');
    Route::get('/tag/{slug}', [PublicController::class, 'tag'])->name('tag.show');
});

/*
|--------------------------------------------------------------------------
| Admin SPA (React)
|--------------------------------------------------------------------------
| All /admin/* pages are served by the same shell; React Router handles
| the client-side pages. Login is public, everything else requires an
| authenticated admin (guests are redirected to /admin/login).
|
*/
Route::get('/admin/login', fn () => view('admin'))
    ->middleware(ShareSiteSettings::class)
    ->name('admin.login');
Route::get('/admin/reset-password', fn () => view('admin'))
    ->middleware(ShareSiteSettings::class)
    ->name('password.reset');
Route::get('/admin/{path?}', fn () => view('admin'))
    ->where('path', '.*')
    ->middleware(['auth', 'admin', ShareSiteSettings::class])
    ->name('admin');

// Storage fallback (serves files if public/storage symlink missing)
Route::get('/storage/{path}', StorageFileController::class)
    ->where('path', '.*')
    ->name('storage.file');

// SEO endpoints
Route::get('/sitemap.xml', [SeoController::class, 'sitemap'])->name('seo.sitemap');
Route::get('/robots.txt', [SeoController::class, 'robots'])->name('seo.robots');
Route::get('/feed.xml', [SeoController::class, 'feed'])
    ->middleware(ShareSiteSettings::class)
    ->name('seo.feed');
