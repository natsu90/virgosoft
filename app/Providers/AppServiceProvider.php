<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Contracts\OrderRepositoryInterface;
use App\Contracts\UserRepositoryInterface;
use App\Contracts\AssetRepositoryInterface;
use App\Contracts\TradeRepositoryInterface;
use App\Repositories\OrderRepository;
use App\Repositories\UserRepository;
use App\Repositories\AssetRepository;
use App\Repositories\TradeRepository;
use App\Observers\OrderObserver;
use App\Observers\UserObserver;
use App\Observers\AssetObserver;
use App\Observers\TradeObserver;
use App\Models\Order;
use App\Models\User;
use App\Models\Asset;
use App\Models\Trade;

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
        $this->app->bind(TradeRepositoryInterface::class, TradeRepository::class);

        Order::observe(OrderObserver::class);
        User::observe(UserObserver::class);
        Asset::observe(AssetObserver::class);
        Trade::observe(TradeObserver::class);
    }
}
