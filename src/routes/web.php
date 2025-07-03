<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DmarcDashboardController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\SettingsController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return redirect('/dashboard');
});

// 認証が必要なルート
Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // DMARC Reports
    Route::get('/dmarc-reports', [DmarcDashboardController::class, 'index'])->name('dmarc-reports.index');
    Route::get('/dmarc-reports/{report}', [DmarcDashboardController::class, 'show'])->name('dmarc-reports.show');

    // Analytics
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics');

    // Settings
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');

    // Settings API
    Route::post('/settings/import-reports', [SettingsController::class, 'importReports'])->name('settings.import-reports');
    Route::post('/settings/upload-reports', [SettingsController::class, 'uploadReports'])->name('settings.upload-reports');
    Route::post('/settings/clear-cache', [SettingsController::class, 'clearCache'])->name('settings.clear-cache');
    Route::post('/settings/cleanup-storage', [SettingsController::class, 'cleanupStorage'])->name('settings.cleanup-storage');
    Route::get('/settings/logs', [SettingsController::class, 'getLogs'])->name('settings.get-logs');
    Route::get('/settings/log-files', [SettingsController::class, 'getLogFiles'])->name('settings.get-log-files');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
