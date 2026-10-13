<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Gate::define('administer', fn (User $user) => $user->is_active && $user->role === 'admin');
        Gate::define('review-news', fn (User $user) => $user->is_active && in_array($user->role, ['admin', 'analyst'], true));
        Gate::define('archive-news', fn (User $user) => $user->is_active && in_array($user->role, ['admin', 'analyst', 'officer'], true));
        RateLimiter::for('login', fn (Request $request) => [
            Limit::perMinute(5)->by(strtolower((string) $request->input('email')).'|'.$request->ip()),
            Limit::perMinute(30)->by($request->ip()),
        ]);
        RateLimiter::for('workflow', fn (Request $request) => Limit::perMinute(1)->by($request->user()->id));
    }
}
