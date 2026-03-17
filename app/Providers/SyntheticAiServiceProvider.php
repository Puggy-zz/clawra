<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Ai\AiManager;

class SyntheticAiServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->afterResolving(AiManager::class, function (AiManager $manager) {
            $manager->extend('synthetic', function ($app, array $config) {
                return new \App\Ai\Providers\SyntheticProvider(
                    new \Laravel\Ai\Gateway\Prism\PrismGateway($app['events']),
                    $config,
                    $app['events']
                );
            });
        });
    }
}
