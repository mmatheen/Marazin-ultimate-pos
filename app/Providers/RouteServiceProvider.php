<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/dashboard';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/api.php'));

            Route::middleware('web')
                ->group(base_path('routes/web.php'));
        });
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            // Increase rate limit for API routes, especially for POS operations
            // Allow 200 requests per minute for authenticated users, 100 for guests
            $limit = $request->user() ? 200 : 100;
            return Limit::perMinute($limit)->by($request->user()?->id ?: $request->ip());
        });

        // Create a separate, more lenient rate limiter for product operations
        RateLimiter::for('product-api', function (Request $request) {
            // Allow more requests for product-related operations (300 per minute)
            return Limit::perMinute(300)->by($request->user()?->id ?: $request->ip());
        });
    }
}
