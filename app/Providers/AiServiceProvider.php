<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\ProviderRegistry;
use App\Services\HeartbeatScheduler;
use App\Agents\CoordinatorAgent;
use App\Agents\PlannerAgent;
use App\Agents\ResearcherAgent;

class AiServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register our AI services
        $this->app->singleton(ProviderRegistry::class, function ($app) {
            return new ProviderRegistry();
        });
        
        $this->app->singleton(HeartbeatScheduler::class, function ($app) {
            return new HeartbeatScheduler(
                $app->make(ProviderRegistry::class)
            );
        });
        
        // Register our agents
        $this->app->singleton(CoordinatorAgent::class, function ($app) {
            return new CoordinatorAgent();
        });
        
        $this->app->singleton(PlannerAgent::class, function ($app) {
            return new PlannerAgent();
        });
        
        $this->app->singleton(ResearcherAgent::class, function ($app) {
            return new ResearcherAgent();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Any bootstrapping needed for our AI services
    }
}
