<?php

declare(strict_types=1);

namespace App\Providers;

use App\Agents\CoordinatorAgent;
use App\Agents\CoordinatorIntentAgent;
use App\Agents\DeveloperAgent;
use App\Agents\PlannerAgent;
use App\Agents\ResearcherAgent;
use App\Agents\ReviewerAgent;
use App\Agents\TestWriterAgent;
use App\Services\AgentService;
use App\Services\ProjectService;
use App\Services\RuntimeExecutionService;
use App\Services\SimpleChatService;
use App\Services\SyntheticSearchService;
use App\Services\TaskService;
use App\Services\WorkflowService;
use Illuminate\Support\ServiceProvider;

class AiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(CoordinatorAgent::class, function ($app): CoordinatorAgent {
            return new CoordinatorAgent(
                $app->make(\App\Services\AiService::class),
                $app->make(ProjectService::class),
                $app->make(TaskService::class),
                $app->make(WorkflowService::class),
                $app->make(SimpleChatService::class),
                $app->make(CoordinatorIntentAgent::class),
                agentService: $app->make(AgentService::class),
                runtimeExecutionService: $app->make(RuntimeExecutionService::class),
            );
        });

        $this->app->singleton(CoordinatorIntentAgent::class, fn (): CoordinatorIntentAgent => new CoordinatorIntentAgent);
        $this->app->singleton(PlannerAgent::class, fn ($app): PlannerAgent => new PlannerAgent($app->make(\App\Services\AiService::class), $app->make(AgentService::class)));
        $this->app->singleton(ResearcherAgent::class, fn ($app): ResearcherAgent => new ResearcherAgent($app->make(\App\Services\AiService::class), $app->make(SyntheticSearchService::class), $app->make(AgentService::class)));
        $this->app->singleton(DeveloperAgent::class, fn ($app): DeveloperAgent => new DeveloperAgent($app->make(\App\Services\AiService::class), $app->make(AgentService::class)));
        $this->app->singleton(ReviewerAgent::class, fn ($app): ReviewerAgent => new ReviewerAgent($app->make(\App\Services\AiService::class), $app->make(AgentService::class)));
        $this->app->singleton(TestWriterAgent::class, fn ($app): TestWriterAgent => new TestWriterAgent($app->make(\App\Services\AiService::class), $app->make(AgentService::class)));
    }

    public function boot(): void {}
}
