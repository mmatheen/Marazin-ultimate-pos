<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Darryldecode\Cart\Cart;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Properly bind the Cart service
        $this->app->singleton(Cart::class, function ($app) {
            return new Cart(
                $app['session.store'],
                $app['events'],
                'default',
                'cart',
                config('shopping_cart', [])
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);
    }
}
