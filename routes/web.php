<?php

use App\Http\Controllers\Web\AuthUserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/**
 *  Welcome Route
 */
Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

Route::get('/terms', function () {
    return Inertia::render('legal', ['document' => 'terms']);
})->name('terms');

Route::get('/privacy', function () {
    return Inertia::render('legal', ['document' => 'privacy']);
})->name('privacy');

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
    Route::get('/dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    Route::get('/settings/profile', function () {
        return Inertia::render('settings/profile');
    })->name('profile.edit');

    Route::get('/settings/security', function () {
        return Inertia::render('settings/security');
    })->name('security.edit');
});

Route::get('/forbidden', function () {
    return Inertia::render('errors/forbidden');
})->name('forbidden');
