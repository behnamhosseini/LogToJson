<?php

namespace Behnamhosseini\LogToJson\Providers;

use Illuminate\Support\ServiceProvider;
use Behnamhosseini\LogToJson\LogManager;

class LogServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function boot(){
        $this->publishes([
            __DIR__.'/../config/config.php' => config_path('logToJson.php'),
        ]);
    }
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/config.php', 'logToJson');

        $this->app->extend('log', function () {
            return  new LogManager($this->app);
        });
    }
}
