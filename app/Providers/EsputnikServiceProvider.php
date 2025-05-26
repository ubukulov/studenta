<?php

namespace App\Providers;

use App\Classes\Esputnik;
use Illuminate\Support\ServiceProvider;

class EsputnikServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('esputnik', Esputnik::class);
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
