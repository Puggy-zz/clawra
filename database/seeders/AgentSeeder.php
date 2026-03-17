<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Agents\CoordinatorAgent as CoordinatorRuntime;
use App\Agents\PlannerAgent as PlannerRuntime;
use App\Agents\ResearcherAgent as ResearcherRuntime;
use App\Models\Agent;
use App\Models\AgentRuntime;
use App\Models\ProviderModel;
use App\Models\ProviderRoute;
use Illuminate\Database\Seeder;

class AgentSeeder extends Seeder
{
    public function run(): void
    {
        $clawra = Agent::query()->updateOrCreate(
            ['name' => 'Clawra'],
            [
                'role' => 'Coordinator',
                'description' => 'Routes requests, persists state, and manages Phase 0 orchestration.',
                'status' => 'active',
                'model' => 'synthetic',
                'fallback_model' => 'gemini',
                'tools' => ['coordination', 'project_state', 'provider_registry'],
                'execution_preferences' => ['preferred_harness' => 'laravel_ai'],
            ]
        );

        $researcher = Agent::query()->updateOrCreate(
            ['name' => 'Researcher'],
            [
                'role' => 'Research Specialist',
                'description' => 'Conducts web research through synthetic search.',
                'status' => 'active',
                'model' => 'synthetic',
                'fallback_model' => 'gemini',
                'tools' => ['web_search', 'summarization'],
                'execution_preferences' => ['preferred_harness' => 'laravel_ai'],
            ]
        );

        $developer = Agent::query()->updateOrCreate(
            ['name' => 'Developer'],
            [
                'role' => 'Code Implementation Specialist',
                'description' => 'Reserved for Phase 1 sandbox execution.',
                'status' => 'active',
                'model' => 'synthetic',
                'fallback_model' => 'gemini',
                'tools' => ['code_editor', 'terminal'],
                'execution_preferences' => ['preferred_harness' => 'opencode'],
            ]
        );

        $reviewer = Agent::query()->updateOrCreate(
            ['name' => 'Reviewer'],
            [
                'role' => 'Quality Assurance Specialist',
                'description' => 'Reserved for review workflows.',
                'status' => 'active',
                'model' => 'synthetic',
                'fallback_model' => 'gemini',
                'tools' => ['review'],
                'execution_preferences' => ['preferred_harness' => 'opencode'],
            ]
        );

        $planner = Agent::query()->updateOrCreate(
            ['name' => 'Planner'],
            [
                'role' => 'Task Planning Specialist',
                'description' => 'Builds project plans and task breakdowns.',
                'status' => 'active',
                'model' => 'synthetic',
                'fallback_model' => 'gemini',
                'tools' => ['task_planning', 'project_breakdown'],
                'execution_preferences' => ['preferred_harness' => 'laravel_ai'],
            ]
        );

        $testWriter = Agent::query()->updateOrCreate(
            ['name' => 'Test Writer'],
            [
                'role' => 'Test Authoring Specialist',
                'description' => 'Reserved for TDD workflows in Phase 1.',
                'status' => 'active',
                'model' => 'synthetic',
                'fallback_model' => 'gemini',
                'tools' => ['testing'],
                'execution_preferences' => ['preferred_harness' => 'opencode'],
            ]
        );

        $this->seedRuntime($clawra, 'laravel_ai', 'primary', 'laravel_class', CoordinatorRuntime::class, 'synthetic-laravel-ai', 'deepseek-v3', 'gemini-laravel-ai', 'gemini-2.5-pro', true, ['coordination', 'project_state', 'provider_registry']);
        $this->seedRuntime($clawra, 'opencode', 'concierge', 'opencode_agent', 'general', 'openai-opencode-chatgpt', 'gpt-5.4', 'synthetic-opencode', 'deepseek-v3', false, ['chat', 'coordination']);

        $this->seedRuntime($planner, 'laravel_ai', 'planner-core', 'laravel_class', PlannerRuntime::class, 'synthetic-laravel-ai', 'kimi-k2-instruct', 'gemini-laravel-ai', 'gemini-2.5-pro', true, ['task_planning', 'project_breakdown']);
        $this->seedRuntime($planner, 'opencode', 'planner-sidecar', 'opencode_agent', 'plan', 'openai-opencode-chatgpt', 'gpt-5.4', 'synthetic-opencode', 'kimi-k2-instruct', false, ['task_planning']);

        $this->seedRuntime($researcher, 'laravel_ai', 'research-core', 'laravel_class', ResearcherRuntime::class, 'synthetic-laravel-ai', 'deepseek-v3', 'gemini-laravel-ai', 'gemini-2.5-pro', true, ['web_search', 'summarization']);
        $this->seedRuntime($researcher, 'opencode', 'research-sidecar', 'opencode_agent', 'explore', 'synthetic-opencode', 'deepseek-v3', 'gemini-opencode', 'gemini-2.5-pro', false, ['web_search']);

        $this->seedRuntime($developer, 'opencode', 'builder', 'opencode_agent', 'build', 'openai-opencode-chatgpt', 'gpt-5.4', 'synthetic-opencode', 'deepseek-v3', true, ['code_editor', 'terminal']);
        $this->seedRuntime($developer, 'codex', 'worker', 'codex_subagent', 'worker', 'openai-codex-chatgpt', 'gpt-5.4', null, null, false, ['code_editor', 'terminal']);

        $this->seedRuntime($reviewer, 'opencode', 'reviewer', 'opencode_agent', 'general', 'openai-opencode-chatgpt', 'gpt-5.4', 'synthetic-opencode', 'deepseek-v3', true, ['review']);
        $this->seedRuntime($reviewer, 'codex', 'review-worker', 'codex_subagent', 'explorer', 'openai-codex-chatgpt', 'gpt-5.4', null, null, false, ['review']);

        $this->seedRuntime($testWriter, 'opencode', 'test-writer', 'opencode_agent', 'build', 'openai-opencode-chatgpt', 'gpt-5.4', 'synthetic-opencode', 'deepseek-v3', true, ['testing']);
        $this->seedRuntime($testWriter, 'codex', 'test-worker', 'codex_subagent', 'worker', 'openai-codex-chatgpt', 'gpt-5.3-codex', null, null, false, ['testing']);
    }

    protected function seedRuntime(
        Agent $agent,
        string $harness,
        string $name,
        string $runtimeType,
        string $runtimeRef,
        ?string $routeName,
        ?string $modelName,
        ?string $fallbackRouteName,
        ?string $fallbackModelName,
        bool $isDefault,
        array $tools,
    ): AgentRuntime {
        $route = $routeName ? ProviderRoute::query()->where('name', $routeName)->first() : null;
        $model = $route && $modelName
            ? ProviderModel::query()->where('provider_route_id', $route->id)->where('name', $modelName)->first()
            : null;

        $fallbackRoute = $fallbackRouteName ? ProviderRoute::query()->where('name', $fallbackRouteName)->first() : null;
        $fallbackModel = $fallbackRoute && $fallbackModelName
            ? ProviderModel::query()->where('provider_route_id', $fallbackRoute->id)->where('name', $fallbackModelName)->first()
            : null;

        return AgentRuntime::query()->updateOrCreate(
            [
                'agent_id' => $agent->id,
                'harness' => $harness,
                'name' => $name,
            ],
            [
                'provider_route_id' => $route?->id,
                'provider_model_id' => $model?->id,
                'fallback_provider_route_id' => $fallbackRoute?->id,
                'fallback_provider_model_id' => $fallbackModel?->id,
                'runtime_type' => $runtimeType,
                'runtime_ref' => $runtimeRef,
                'description' => sprintf('%s runtime on %s.', $agent->name, $harness),
                'tools' => $tools,
                'config' => [],
                'is_default' => $isDefault,
                'status' => 'active',
            ]
        );
    }
}
