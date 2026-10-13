<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active'])->group(function () {
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/export', [DashboardController::class, 'export'])->name('dashboard.export');
Route::get('/berita/{berita}', [DashboardController::class, 'show'])->name('berita.show');
Route::post('/berita/{berita}/toggle-archive', [DashboardController::class, 'toggleArchive'])->can('archive-news')->name('berita.toggle-archive');
Route::get('/check-urgent', [DashboardController::class, 'checkUrgent'])->name('dashboard.check-urgent');
Route::get('/statistik-sumber', [DashboardController::class, 'sources'])->name('dashboard.sources');
Route::post('/jalankan-pencarian', [DashboardController::class, 'runWorkflow'])->can('review-news')->middleware('throttle:workflow')->name('dashboard.run-workflow');

Route::get('/berita/{berita}/review', [\App\Http\Controllers\NewsReviewController::class, 'edit'])->can('review-news')->name('news.review');
Route::put('/berita/{berita}/review', [\App\Http\Controllers\NewsReviewController::class, 'update'])->can('review-news')->name('news.review.update');
Route::middleware('can:administer')->prefix('admin')->name('admin.')->group(function () {
    Route::resource('users', \App\Http\Controllers\UserController::class)->only(['index', 'store', 'update']);
    Route::get('taxonomies', [\App\Http\Controllers\TaxonomyController::class, 'index'])->name('taxonomies.index');
    Route::post('taxonomies', [\App\Http\Controllers\TaxonomyController::class, 'store'])->name('taxonomies.store');
    Route::put('taxonomies/{taxonomy}', [\App\Http\Controllers\TaxonomyController::class, 'update'])->name('taxonomies.update');
});
Route::post('/logout', [\App\Http\Controllers\AuthController::class, 'destroy'])->name('logout');
});
Route::middleware('guest')->group(function () {
    Route::get('/login', [\App\Http\Controllers\AuthController::class, 'create'])->name('login');
    Route::post('/login', [\App\Http\Controllers\AuthController::class, 'store'])->middleware('throttle:login');
});
