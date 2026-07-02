<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Prevent lazy loading in non-production to catch N+1 issues early
        Model::preventLazyLoading(! app()->isProduction());
        // ── Rate Limiters ────────────────────────────────────────

        // General API: 60 requests per minute per user/IP
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by(
                $request->user()?->id ?: $request->ip()
            );
        });

        // Auth (login/register): 5 attempts per minute per IP
        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(5)->by(
                $request->ip()
            )->response(function () {
                return response()->json([
                    'success' => false,
                    'message' => 'Terlalu banyak percobaan. Silakan coba lagi dalam 1 menit.',
                ], 429);
            });
        });

        // AI Chat: 10 requests per minute per user/IP
        RateLimiter::for('ai_chat', function (Request $request) {
            return Limit::perMinute(10)->by(
                $request->user()?->id ?: $request->ip()
            )->response(function () {
                return response()->json([
                    'success' => false,
                    'message' => 'Terlalu banyak permintaan chat. Silakan tunggu sebentar.',
                ], 429);
            });
        });
    }
}
