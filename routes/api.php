<?php

use App\Http\Controllers\Api\NewsIngestController;
use App\Http\Controllers\Api\NewsApiController;
use Illuminate\Support\Facades\Route;

// Dipanggil oleh node HTTP Request di n8n
Route::post('/n8n/news', [NewsIngestController::class, 'store']);

// Dikonsumsi oleh aplikasi Android (Kotlin)
Route::get('/news', [NewsApiController::class, 'index']);
Route::get('/news/{news}', [NewsApiController::class, 'show']);
Route::post('/news/{news}/toggle-archive', [NewsApiController::class, 'toggleArchive']);
Route::get('/stats', [NewsApiController::class, 'stats']);
Route::get('/sources', [NewsApiController::class, 'sources']);
Route::get('/trend', [NewsApiController::class, 'trend']);
Route::get('/weekly', [NewsApiController::class, 'weekly']);
