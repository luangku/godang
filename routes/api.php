<?php

use App\Http\Controllers\Api\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Api\Admin\CommentController as AdminCommentController;
use App\Http\Controllers\Api\Admin\DashboardController;
use App\Http\Controllers\Api\Admin\MediaCategoryController;
use App\Http\Controllers\Api\Admin\MediaController;
use App\Http\Controllers\Api\Admin\PostController as AdminPostController;
use App\Http\Controllers\Api\Admin\SettingController;
use App\Http\Controllers\Api\Admin\TagController as AdminTagController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\CommentController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\SiteController;
use App\Http\Controllers\Api\TagController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public API Routes
|--------------------------------------------------------------------------
*/

Route::get('/posts', [PostController::class, 'index'])->name('api.posts.index');
Route::get('/posts/{slug}', [PostController::class, 'show'])->name('api.posts.show');
Route::get('/posts/{post}/comments', [CommentController::class, 'index'])->name('api.comments.index');
Route::post('/posts/{post}/comments', [CommentController::class, 'store'])
    ->middleware('throttle:6,1')
    ->name('api.comments.store');
Route::get('/categories', [CategoryController::class, 'index'])->name('api.categories.index');
Route::get('/tags', [TagController::class, 'index'])->name('api.tags.index');
Route::get('/site/settings', [SiteController::class, 'settings'])->name('api.site.settings');

/*
|--------------------------------------------------------------------------
| Auth Routes
|--------------------------------------------------------------------------
*/

Route::post('/auth/login', [AuthController::class, 'login'])
    ->middleware('throttle:login')
    ->name('api.auth.login');
Route::post('/auth/forgot-password', [AuthController::class, 'forgotPassword'])->name('api.auth.forgot-password');
Route::post('/auth/reset-password', [AuthController::class, 'resetPassword'])->name('api.auth.reset-password');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [AuthController::class, 'logout'])->name('api.auth.logout');
    Route::get('/auth/me', [AuthController::class, 'me'])->name('api.auth.me');
});

/*
|--------------------------------------------------------------------------
| Admin API Routes (auth:sanctum)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->name('api.admin.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Posts CRUD
    Route::post('/posts/bulk-delete', [AdminPostController::class, 'bulkDelete'])->name('posts.bulk-delete');
    Route::post('/posts/{post}/restore', [AdminPostController::class, 'restore'])->withTrashed()->name('posts.restore');
    Route::apiResource('posts', AdminPostController::class)->withTrashed(['show', 'update', 'destroy']);

    // Categories CRUD
    Route::apiResource('categories', AdminCategoryController::class);

    // Tags CRUD
    Route::apiResource('tags', AdminTagController::class);

    // Comments moderation
    Route::get('/comments', [AdminCommentController::class, 'index'])->name('comments.index');
    Route::post('/comments', [AdminCommentController::class, 'store'])->name('comments.store');
    Route::patch('/comments/{comment}', [AdminCommentController::class, 'approve'])->name('comments.approve');
    Route::put('/comments/{comment}', [AdminCommentController::class, 'update'])->name('comments.update');
    Route::patch('/comments/{comment}/spam', [AdminCommentController::class, 'markSpam'])->name('comments.spam');
    Route::post('/comments/{comment}/reply', [AdminCommentController::class, 'reply'])->name('comments.reply');
    Route::delete('/comments/{comment}', [AdminCommentController::class, 'destroy'])->name('comments.destroy');
    Route::post('/comments/bulk-delete', [AdminCommentController::class, 'bulkDelete'])->name('comments.bulk-delete');

    // Media
    Route::get('/media', [MediaController::class, 'index'])->name('media.index');
    Route::post('/media', [MediaController::class, 'store'])->name('media.store');
    Route::post('/media/bulk-delete', [MediaController::class, 'bulkDelete'])->name('media.bulk-delete');
    Route::post('/media/bulk-restore', [MediaController::class, 'bulkRestore'])->name('media.bulk-restore');
    Route::post('/media/bulk-force-delete', [MediaController::class, 'bulkForceDelete'])->name('media.bulk-force-delete');
    Route::patch('/media/{media}', [MediaController::class, 'update'])->name('media.update');
    Route::delete('/media/{media}', [MediaController::class, 'destroy'])->name('media.destroy');
    Route::post('/media/{media}/restore', [MediaController::class, 'restore'])->withTrashed()->name('media.restore');
    Route::delete('/media/{media}/force', [MediaController::class, 'forceDelete'])->withTrashed()->name('media.force-delete');

    // Media categories
    Route::get('/media-categories', [MediaCategoryController::class, 'index'])->name('media-categories.index');
    Route::post('/media-categories', [MediaCategoryController::class, 'store'])->name('media-categories.store');
    Route::patch('/media-categories/{mediaCategory}', [MediaCategoryController::class, 'update'])->name('media-categories.update');
    Route::delete('/media-categories/{mediaCategory}', [MediaCategoryController::class, 'destroy'])->name('media-categories.destroy');

    // Settings
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
});
