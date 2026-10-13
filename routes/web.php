<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\IssueArticleController;
use App\Http\Controllers\IssueController;
use App\Http\Controllers\NewsReviewController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TaxonomyController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WorkflowController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/export', [DashboardController::class, 'export'])->name('dashboard.export');
    Route::get('/berita/{berita}', [DashboardController::class, 'show'])->name('berita.show');
    Route::post('/berita/{berita}/toggle-archive', [DashboardController::class, 'toggleArchive'])->can('archive-news')->name('berita.toggle-archive');
    Route::get('/check-urgent', [DashboardController::class, 'checkUrgent'])->name('dashboard.check-urgent');
    Route::get('/statistik-sumber', [DashboardController::class, 'sources'])->name('dashboard.sources');
    Route::post('/jalankan-pencarian', [DashboardController::class, 'runWorkflow'])->can('review-news')->middleware('throttle:workflow')->name('dashboard.run-workflow');

    Route::get('/berita/{berita}/review', [NewsReviewController::class, 'edit'])->can('review-news')->name('news.review');
    Route::put('/berita/{berita}/review', [NewsReviewController::class, 'update'])->can('review-news')->name('news.review.update');
    Route::middleware('can:administer')->prefix('admin')->name('admin.')->group(function () {
        Route::resource('users', UserController::class)->only(['index', 'store', 'update']);
        Route::get('taxonomies', [TaxonomyController::class, 'index'])->name('taxonomies.index');
        Route::post('taxonomies', [TaxonomyController::class, 'store'])->name('taxonomies.store');
        Route::put('taxonomies/{taxonomy}', [TaxonomyController::class, 'update'])->name('taxonomies.update');
    });
    Route::resource('issues', IssueController::class)->only(['index', 'create', 'store', 'show', 'update']);
    Route::post('/issues/{issue}/comments', [IssueController::class, 'comment'])->name('issues.comment');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::put('/notifications/preferences', [NotificationController::class, 'preferences'])->name('notifications.preferences');
    Route::post('/issues/{issue}/articles', [IssueArticleController::class, 'store'])->name('issues.articles.store');
    Route::delete('/issues/{issue}/articles/{news}', [IssueArticleController::class, 'destroy'])->name('issues.articles.destroy');
    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::post('/reports', [ReportController::class, 'store'])->can('review-news')->name('reports.store');
    Route::get('/reports/{report}', [ReportController::class, 'show'])->name('reports.show');
    Route::get('/reports/{report}/pdf', [ReportController::class, 'pdf'])->name('reports.pdf');
    Route::get('/workflow', [WorkflowController::class, 'index'])->can('review-news')->name('workflow.index');
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
});
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:login');
});
