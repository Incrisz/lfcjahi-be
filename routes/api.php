<?php

use App\Http\Controllers\Api\Admin\BlogPostController;
use App\Http\Controllers\Api\Admin\CategoryController;
use App\Http\Controllers\Api\Admin\EventController;
use App\Http\Controllers\Api\Admin\MediaItemController;
use App\Http\Controllers\Api\Admin\ThemeSettingController;
use App\Http\Controllers\Api\PublicMediaController;
use Illuminate\Support\Facades\Route;

Route::get('media', [PublicMediaController::class, 'index']);
Route::get('media/{id}/download', [PublicMediaController::class, 'download']);

Route::prefix('admin')->group(function (): void {
    Route::get('categories', [CategoryController::class, 'index']);
    Route::post('categories', [CategoryController::class, 'store']);
    Route::put('categories/{category}', [CategoryController::class, 'update']);
    Route::delete('categories/{category}', [CategoryController::class, 'destroy']);

    Route::post('categories/{category}/subcategories', [CategoryController::class, 'storeSubcategory']);
    Route::put('subcategories/{subcategory}', [CategoryController::class, 'updateSubcategory']);
    Route::delete('subcategories/{subcategory}', [CategoryController::class, 'destroySubcategory']);

    Route::apiResource('media', MediaItemController::class);
    Route::patch('media/{id}/publish', [MediaItemController::class, 'updatePublishStatus']);
    Route::apiResource('events', EventController::class);
    Route::apiResource('blog-posts', BlogPostController::class);

    Route::get('theme-settings', [ThemeSettingController::class, 'show']);
    Route::put('theme-settings', [ThemeSettingController::class, 'update']);
});
