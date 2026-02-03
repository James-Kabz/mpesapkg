<?php

namespace JamesKabz\MpesaPkg\Providers;

use Illuminate\Support\ServiceProvider;
use JamesKabz\MpesaPkg\MpesaClient;
use JamesKabz\MpesaPkg\Console\GenerateSecurityCredential;

class MpesaServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/mpesa.php', 'mpesa');

        $this->app->singleton(MpesaClient::class, function ($app) {
            return new MpesaClient($app['config']->get('mpesa', []));
        });
    }

    public function boot()
    {
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');

        $this->publishes([
            __DIR__ . '/../Config/mpesa.php' => config_path('mpesa.php'),
        ], 'mpesa-config');

        // load migrations
        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        if ($this->app->runningInConsole()) {
            $this->commands([
                GenerateSecurityCredential::class,
            ]);
        }
    }
}
