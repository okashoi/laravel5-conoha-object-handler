<?php

namespace Okashoi\Laravel5ConohaObjectHandler;

use Illuminate\Support\ServiceProvider;

/**
 * Class ConohaObjectServiceProvider
 * @package Okashoi\Laravel5ConohaObjectHandler
 *
 * The Laravel Service Provider for Conoha Object Service
 */
class ConohaObjectServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/config/conoha.php' => config_path('conoha.php'),
        ]);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton(ObjectHandler::class, function ($app) {
            return new ObjectHandler();
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return [ObjectHandler::class];
    }

}
