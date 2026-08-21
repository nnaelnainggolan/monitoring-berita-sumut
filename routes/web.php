<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/export', [DashboardController::class, 'export'])->name('dashboard.export');
Route::get('/berita/{berita}', [DashboardController::class, 'show'])->name('berita.show');
Route::post('/berita/{berita}/toggle-archive', [DashboardController::class, 'toggleArchive'])->name('berita.toggle-archive');
Route::get('/check-urgent', [DashboardController::class, 'checkUrgent'])->name('dashboard.check-urgent');
Route::get('/statistik-sumber', [DashboardController::class, 'sources'])->name('dashboard.sources');
Route::post('/jalankan-pencarian', [DashboardController::class, 'runWorkflow'])->name('dashboard.run-workflow');
