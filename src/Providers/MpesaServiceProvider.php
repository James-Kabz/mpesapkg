<?php

namespace JamesKabz\MpesaPkg\Providers;

use Illuminate\Support\ServiceProvider;
use JamesKabz\MpesaPkg\MpesaClient;
use JamesKabz\MpesaPkg\Console\GenerateSecurityCredential;
use JamesKabz\MpesaPkg\Services\MpesaConfig;
use JamesKabz\MpesaPkg\Services\MpesaHelper;

class MpesaServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../Config/mpesa.php', 'mpesa');

        $this->app->singleton(MpesaConfig::class, function ($app) {
            return new MpesaConfig($app['config']);
        });

        $this->app->singleton(MpesaHelper::class, function ($app) {
            return new MpesaHelper($app->make(MpesaConfig::class));
        });

        $this->app->singleton(MpesaClient::class, function ($app) {
            $config = $app->make(MpesaConfig::class);
            $helper = $app->make(MpesaHelper::class);
            return new MpesaClient($config, $helper);
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
