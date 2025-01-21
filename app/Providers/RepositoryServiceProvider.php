<?php

namespace App\Providers;

use App\Interfaces\AuthInterface;
use App\Interfaces\OrderInterface;
use App\Interfaces\GeneralInterface;
use App\Interfaces\ProductInterface;
use App\Repositories\AuthRepository;
use App\Repositories\OrderRepository;
use App\Interfaces\OrderItemInterface;
use App\Repositories\GeneralRepository;
use App\Repositories\ProductRepository;
use Illuminate\Support\ServiceProvider;
use App\Repositories\OrderItemRepository;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(OrderInterface::class, OrderRepository::class);
        $this->app->bind(OrderItemInterface::class, OrderItemRepository::class);
        $this->app->bind(ProductInterface::class, ProductRepository::class);
        $this->app->bind(AuthInterface::class, AuthRepository::class);
        $this->app->bind(GeneralInterface::class, GeneralRepository::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
