<?php

use App\Enums\ConverterType;
use App\Http\Controllers\Web\Admin\CmsPageController;
use App\Http\Controllers\Web\AuthUserController;
use App\Http\Controllers\Web\ConversionController;
use App\Http\Controllers\Web\MachineProfileController;
use App\Http\Controllers\Web\ProjectController;
use App\Http\Controllers\Web\PublicPageController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/**
 *  Welcome Route
 */
Route::get('/', [PublicPageController::class, 'home'])->name('home');
Route::get('/pages/{slug}', [PublicPageController::class, 'show'])->name('pages.show');

Route::get('/terms', [PublicPageController::class, 'legal'])
    ->defaults('document', 'terms')
    ->name('terms');

Route::get('/privacy', [PublicPageController::class, 'legal'])
    ->defaults('document', 'privacy')
    ->name('privacy');

/**
 * Guest Auth Routes
 */
Route::middleware('guest')->group(function () {
    Route::get('/auth/{provider}/redirect', [AuthUserController::class, 'redirectToProvider'])->name('socialite.redirect');
    Route::get('/auth/{provider}/callback', [AuthUserController::class, 'handleProviderCallback'])->name('socialite.callback');
});

/**
 * Auth Routes
 */
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [ProjectController::class, 'dashboard'])->name('dashboard');
    Route::middleware('can:gcode.use')->group(function () {
        Route::resource('projects', ProjectController::class)->except(['create', 'edit']);
        Route::resource('machine-profiles', MachineProfileController::class)->parameters(['machine-profiles' => 'machineProfile'])->only(['index', 'store', 'update', 'destroy']);
        Route::post('/conversions/{converter}', [ConversionController::class, 'store'])->whereIn('converter', ConverterType::values())->middleware('throttle:30,1')->name('conversions.store');
        Route::get('/conversions/{conversion}', [ConversionController::class, 'show'])->name('conversions.show');
        Route::get('/conversions/{conversion}/download', [ConversionController::class, 'download'])->name('conversions.download');
    });

    Route::get('/settings/profile', function () {
        return Inertia::render('settings/profile');
    })->name('profile.edit');

    Route::get('/settings/security', function () {
        return Inertia::render('settings/security');
    })->name('security.edit');
});

Route::prefix('admin/cms')
    ->name('admin.cms.')
    ->middleware(['auth', 'verified', 'can:cms.manage'])
    ->group(function () {
        Route::get('pages/{cmsPage}/preview', [CmsPageController::class, 'preview'])->name('pages.preview');
        Route::post('pages/{cmsPage}/revisions/{revision}/restore', [CmsPageController::class, 'restore'])->name('pages.restore');
        Route::resource('pages', CmsPageController::class)
            ->parameters(['pages' => 'cmsPage'])
            ->except(['show', 'create']);
        Route::post('pages/{cmsPage}/publish', [CmsPageController::class, 'publish'])
            ->middleware('can:cms.publish')
            ->name('pages.publish');
    });

Route::get('/forbidden', function () {
    return Inertia::render('errors/forbidden');
})->name('forbidden');
