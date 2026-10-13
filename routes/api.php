<?php

use App\Enums\ConverterType;
use App\Http\Controllers\Api\v1\Admin\CmsPageController;
use App\Http\Controllers\Api\v1\AuthUserController;
use App\Http\Controllers\Api\v1\ConversionController;
use App\Http\Controllers\Api\v1\MachineProfileController;
use App\Http\Controllers\Api\v1\ProjectController;
use App\Http\Controllers\Api\v1\PublicContentController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/content/pages/{slug}', [PublicContentController::class, 'show']);

    Route::get('/email/verify/{id}/{hash}', [AuthUserController::class, 'verifyEmail'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('api.v1.verification.verify');

    /**
     * Guest Auth Routes
     */
    Route::middleware(['guest', 'throttle:api-auth'])->group(function () {
        Route::post('/register', [AuthUserController::class, 'registerUser']);
        Route::post('/login', [AuthUserController::class, 'loginUser']);
        Route::post('/login/two-factor', [AuthUserController::class, 'twoFactor']);
        Route::post('/forgot-password', [AuthUserController::class, 'forgotPassword']);
        Route::post('/reset-password', [AuthUserController::class, 'resetPassword']);
    });

    Route::middleware(['auth:sanctum', 'active'])->group(function () {
        Route::post('/auth/logout-all', [AuthUserController::class, 'logoutAll']);
        Route::get('/auth/me', [AuthUserController::class, 'me']);
        Route::post('/auth/logout', [AuthUserController::class, 'logoutUser']);
        Route::get('/email/verification-status', [AuthUserController::class, 'verificationStatus']);
        Route::post('/email/verification-notification', [AuthUserController::class, 'sendVerificationNotification'])
            ->middleware('throttle:6,1')
            ->name('api.v1.verification.send');
    });

    Route::prefix('cms')
        ->name('api.v1.cms.')
        ->middleware(['auth:sanctum', 'active', 'verified', 'can:cms.manage'])
        ->group(function () {
            Route::post('/pages/{cmsPage}/revisions/{revision}/restore', [CmsPageController::class, 'restore'])->name('pages.restore');
            Route::apiResource('pages', CmsPageController::class)
                ->parameters(['pages' => 'cmsPage']);
            Route::post('/pages/{cmsPage}/publish', [CmsPageController::class, 'publish'])
                ->middleware('can:cms.publish')
                ->name('pages.publish');
        });

    Route::middleware(['auth:sanctum', 'active', 'verified', 'can:gcode.use'])->group(function () {
        Route::prefix('projects')->name('api.v1.projects.')->group(function () {
            Route::get('/', [ProjectController::class, 'index'])->name('index');
            Route::post('/', [ProjectController::class, 'store'])->name('store');
            Route::get('/{project}', [ProjectController::class, 'show'])->name('show');
            Route::match(['put', 'patch'], '/{project}', [ProjectController::class, 'update'])->name('update');
            Route::delete('/{project}', [ProjectController::class, 'destroy'])->name('destroy');
            Route::get('/{project}/conversions', [ConversionController::class, 'index'])->name('conversions.index');
        });

        Route::prefix('machine-profiles')->name('api.v1.machine-profiles.')->group(function () {
            Route::get('/', [MachineProfileController::class, 'index'])->name('index');
            Route::post('/', [MachineProfileController::class, 'store'])->name('store');
            Route::get('/{machineProfile}', [MachineProfileController::class, 'show'])->name('show');
            Route::match(['put', 'patch'], '/{machineProfile}', [MachineProfileController::class, 'update'])->name('update');
            Route::delete('/{machineProfile}', [MachineProfileController::class, 'destroy'])->name('destroy');
        });

        Route::post('/conversions/{converter}', [ConversionController::class, 'store'])
            ->middleware('throttle:30,1')
            ->whereIn('converter', ConverterType::values())
            ->name('api.v1.conversions.store');
        Route::get('/conversions/{conversion}', [ConversionController::class, 'show'])
            ->name('api.v1.conversions.show');
        Route::get('/conversions/{conversion}/download', [ConversionController::class, 'download'])
            ->name('api.v1.conversions.download');
    });
});
