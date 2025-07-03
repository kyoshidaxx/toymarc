<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DmarcReportController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// DMARC Reports API Routes
Route::prefix('dmarc')->group(function () {
    Route::get('/reports', [DmarcReportController::class, 'index']);
    Route::get('/reports/{id}', [DmarcReportController::class, 'show']);
    Route::get('/reports/statistics', [DmarcReportController::class, 'statistics']);
    Route::get('/records', [DmarcReportController::class, 'records']);
    Route::get('/filter-options', [DmarcReportController::class, 'filterOptions']);
}); 