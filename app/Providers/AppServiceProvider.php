<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Contracts\OrderRepositoryInterface;
use App\Contracts\UserRepositoryInterface;
use App\Contracts\AssetRepositoryInterface;
use App\Repositories\OrderRepository;
use App\Repositories\UserRepository;
use App\Repositories\AssetRepository;
use App\Observers\OrderObserver;
use App\Observers\UserObserver;
use App\Observers\AssetObserver;
use App\Models\Order;
use App\Models\User;
use App\Models\Asset;

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
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(AssetRepositoryInterface::class, AssetRepository::class);

        Order::observe(OrderObserver::class);
        User::observe(UserObserver::class);
        Asset::observe(AssetObserver::class);
    }
}
