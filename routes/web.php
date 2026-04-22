<?php

use App\Http\Controllers\OsShellController;
use App\Http\Controllers\Integration\ModuleSnapshotController;
use Illuminate\Support\Facades\Route;

Route::get('/', [OsShellController::class, 'landing'])->name('landing');
Route::get('/plans', [OsShellController::class, 'plans'])->name('plans');
Route::post('/integrations/snapshots/{module}', [ModuleSnapshotController::class, 'store'])->name('integrations.snapshots.store');

Route::middleware('guest')->group(function () {
    Route::get('/login', [OsShellController::class, 'login'])->name('login');
    Route::post('/login', [OsShellController::class, 'authenticate'])->name('login.authenticate');
    Route::get('/onboarding', [OsShellController::class, 'onboarding'])->name('onboarding');
    Route::post('/onboarding', [OsShellController::class, 'storeOnboarding'])->name('onboarding.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [OsShellController::class, 'dashboard'])->name('dashboard');
    Route::get('/website', [OsShellController::class, 'website'])->name('website');
    Route::post('/website/setup', [OsShellController::class, 'updateWebsite'])->name('website.setup');
    Route::post('/website/publish', [OsShellController::class, 'publishWebsite'])->name('website.publish');
    Route::post('/website/starter', [OsShellController::class, 'createWebsiteStarter'])->name('website.starter');
    Route::post('/website/domain', [OsShellController::class, 'connectWebsiteDomain'])->name('website.domain');
    Route::post('/assistant/chat', [OsShellController::class, 'assistantChat'])->name('assistant.chat');
    Route::post('/logout', [OsShellController::class, 'logout'])->name('logout');
});
