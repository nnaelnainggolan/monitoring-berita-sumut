<?php

use App\Http\Controllers\Api\NewsApiController;
use App\Http\Controllers\Api\NewsIngestController;
use App\Http\Controllers\Api\WorkflowRunController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

// Dipanggil oleh node HTTP Request di n8n
Route::post('/n8n/news', [NewsIngestController::class, 'store'])->middleware(['ingest', 'throttle:120,1']);

Route::middleware(['ingest', 'throttle:120,1'])->group(function () {
    Route::post('/n8n/runs', [WorkflowRunController::class, 'store'])->name('n8n.runs.store');
    Route::patch('/n8n/runs/{run}', [WorkflowRunController::class, 'update'])->name('n8n.runs.update');
});
Route::post('/auth/token', [AuthController::class, 'token'])->middleware('throttle:login');
Route::middleware(['auth:sanctum', 'active'])->group(function () {
    Route::delete('/auth/token', [AuthController::class, 'revoke']);

    // Dikonsumsi oleh aplikasi Android (Kotlin)
    Route::get('/news', [NewsApiController::class, 'index']);
    Route::get('/news/{news}', [NewsApiController::class, 'show']);
    Route::post('/news/{news}/toggle-archive', [NewsApiController::class, 'toggleArchive'])->can('archive-news');
    Route::get('/stats', [NewsApiController::class, 'stats']);
    Route::get('/sources', [NewsApiController::class, 'sources']);
    Route::get('/trend', [NewsApiController::class, 'trend']);
    Route::get('/weekly', [NewsApiController::class, 'weekly']);

});
