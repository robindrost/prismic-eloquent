<?php

namespace RobinDrost\PrismicEloquent\Providers;

use Illuminate\Support\ServiceProvider as LaravelServiceProvider;

class ServiceProvider extends LaravelServiceProvider
{
    public function boot()
    {
        $this->publishes([
            __DIR__ . '/../config/prismiceloquent.php' => config_path('prismiceloquent.php'),
        ]);
    }

    public function register()
    {
        $this->app->singleton(\Prismic\Api::class, function () {
            return \Prismic\Api::get(config('prismiceloquent.url'), config('prismiceloquent.access_token'));
        });
    }
}
