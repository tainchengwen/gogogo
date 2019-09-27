<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Bind the interface to an implementation repository class
     */
    public function register()
    {
        // $this->app->bind('App\Repositories\CartRepository');
        // $this->app->bind('App\Repositories\SPURepository');
        // $this->app->bind('App\Repositories\CategoryRepository');
        // $this->app->bind('App\Repositories\WarehouseRepository');
        // $this->app->bind('App\Repositories\SKURepository');
    }
}