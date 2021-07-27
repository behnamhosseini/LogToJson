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
    public function register()
    {
        $this->app->extend('log', function () {
            return  new LogManager($this->app);
        });
    }
}
