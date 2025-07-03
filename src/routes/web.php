<?php

use App\Http\Controllers\DmarcDashboardController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\SettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/dashboard');
});

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
Route::post('/settings/clear-cache', [SettingsController::class, 'clearCache'])->name('settings.clear-cache');
Route::get('/settings/logs', [SettingsController::class, 'getLogs'])->name('settings.get-logs');
Route::get('/settings/log-files', [SettingsController::class, 'getLogFiles'])->name('settings.get-log-files');
