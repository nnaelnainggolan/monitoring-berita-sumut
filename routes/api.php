<?php

use App\Http\Controllers\Api\NewsIngestController;
use Illuminate\Support\Facades\Route;

// Dipanggil oleh node HTTP Request di n8n
Route::post('/n8n/news', [NewsIngestController::class, 'store']);
