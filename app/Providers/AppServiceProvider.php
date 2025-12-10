<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Contracts\OrderRepositoryInterface;
use App\Repositories\OrderRepository;
use App\Observers\OrderObserver;
use App\Models\Order;

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
        $this->app->bind(OrderRepositoryInterface::class, OrderRepository::class);

        Order::observe(OrderObserver::class);
    }
}
