<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\AgentService;
use App\Services\AiService;
use App\Services\HeartbeatScheduler;
use App\Services\LogService;
use App\Services\ProjectService;
use App\Services\ProviderRegistry;
use App\Services\ProviderService;
use App\Services\SubtaskService;
use App\Services\SyntheticSearchService;
use App\Services\TaskService;
use App\Services\WorkflowService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ProviderRegistry::class, fn (): ProviderRegistry => new ProviderRegistry);
        $this->app->singleton(AiService::class, fn ($app): AiService => new AiService($app->make(ProviderRegistry::class)));
        $this->app->singleton(SyntheticSearchService::class, fn (): SyntheticSearchService => new SyntheticSearchService);
        $this->app->singleton(ProjectService::class, fn (): ProjectService => new ProjectService);
        $this->app->singleton(WorkflowService::class, fn (): WorkflowService => new WorkflowService);
        $this->app->singleton(SubtaskService::class, fn (): SubtaskService => new SubtaskService);
        $this->app->singleton(TaskService::class, fn ($app): TaskService => new TaskService($app->make(SubtaskService::class)));
        $this->app->singleton(AgentService::class, fn (): AgentService => new AgentService);
        $this->app->singleton(ProviderService::class, fn ($app): ProviderService => new ProviderService($app->make(ProviderRegistry::class)));
        $this->app->singleton(LogService::class, fn (): LogService => new LogService);
        $this->app->singleton(HeartbeatScheduler::class, fn ($app): HeartbeatScheduler => new HeartbeatScheduler($app->make(ProviderRegistry::class)));
    }

    public function boot(): void {}
}
