<?php

use App\Http\Controllers\DmarcDashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/dmarc-reports');
});

Route::get('/dmarc-reports', [DmarcDashboardController::class, 'index'])->name('dmarc-reports.index');
Route::get('/dmarc-reports/{report}', [DmarcDashboardController::class, 'show'])->name('dmarc-reports.show');
