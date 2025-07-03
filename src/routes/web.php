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
