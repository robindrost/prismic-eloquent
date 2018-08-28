<?php

namespace RobinDrost\PrismicEloquent\Providers;

use Illuminate\Support\ServiceProvider as LaravelServiceProvider;
use RobinDrost\PrismicEloquent\Cache;

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
        $this->app->bind('prismiceloquent.cache', Cache::class);

        $this->app->singleton(\Prismic\Api::class, function () {
            return \Prismic\Api::get(
                config('prismiceloquent.url'),
                config('prismiceloquent.access_token'),
                null,
                config('prismiceloquent.cache.enabled') ? resolve('prismiceloquent.cache') : null,
                config('priismiceloquent.cache.ttl', 5)
            );
        });
    }
}
