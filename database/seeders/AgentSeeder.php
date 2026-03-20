<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Agents\CoordinatorAgent as CoordinatorRuntime;
use App\Agents\PlannerAgent as PlannerRuntime;
use App\Models\Agent;
use App\Models\AgentRuntime;
use App\Models\Provider;
use App\Models\ProviderModel;
use App\Models\ProviderRoute;
use App\Services\ResearchService;
use Illuminate\Database\Seeder;

class AgentSeeder extends Seeder
{
    public function run(): void
    {
        // Setup synthetic-native provider with research capabilities
        $synthetic = Provider::query()->updateOrCreate(
            ['name' => 'synthetic-native'],
            [
                'name' => 'synthetic-native',
                'type' => 'api-key-based',
                'api_protocol' => 'native',
                'status' => 'active',
                'usage_snapshot' => [
                    'total_requests' => 0,
                    'requests_this_window' => 0,
                    'concurrent_calls' => 0,
                    'last_reset' => now()->toIso8601String(),
                ],
                'rate_limits' => [
                    'requests_per_5hour_window' => 135,
                ],
                'priority_preferences' => [
                    'reasoning' => 99,
                    'web-search' => 85,
                    'code-generation' => 80,
                    'summarization' => 95,
                    'json-schema' => 85,
                    'embeddings' => 50,
                ],
            ]
        );

        // Ensure synthetic-laravel-ai route exists for this provider
        $syntheticRoute = ProviderRoute::query()->where('name', 'synthetic-laravel-ai')->first();

        // Create NVIDIA Nemotron model for deep research
        $nemotron = ProviderModel::query()->updateOrCreate(
            ['name' => 'hf:nvidia/NVIDIA-Nemotron-3-Super-120B-A12B-NVFP4'],
            [
                'provider_route_id' => $syntheticRoute->id,
                'name' => 'hf:nvidia/NVIDIA-Nemotron-3-Super-120B-A12B-NVFP4',
                'external_name' => 'NVIDIA Nemotron 3-Super 120B',
                'config' => [
                    'metadata' => [
                        'parameters' => 120_000_000_000,
                        'context_window' => 256_000,
                        'finish_reasons' => ['STOP', 'LENGTH', 'ERROR'],
                        'license' => 'proprietary',
                    ],
                ],
                'capabilities' => [
                    'reasoning' => true,
                    'web-search' => true,
                    'json-schema' => true,
                    'code-generation' => true,
                    'summarization' => true,
                ],
                'context_window' => 256_000,
                'is_default' => true,
                'status' => 'active',
            ]
        );

        $clawra = Agent::query()->updateOrCreate(
            ['name' => 'Clawra'],
            [
                'role' => 'Coordinator',
                'description' => 'Routes requests, persists state, and manages Phase 0 orchestration.',
                'status' => 'active',
                'model' => 'hf:nvidia/NVIDIA-Nemotron-3-Super-120B-A12B-NVFP4',
                'fallback_model' => 'gemini',
                'tools' => ['coordination', 'project_state', 'provider_registry'],
                'execution_preferences' => ['preferred_harness' => 'laravel_ai'],
            ]
        );

        $researcher = Agent::query()->updateOrCreate(
            ['name' => 'Researcher'],
            [
                'role' => 'Deep Research Specialist',
                'description' => 'Performs comprehensive multi-source web research using synthetic search and tracks token usage for 256k context window.',
                'status' => 'active',
                'model' => 'hf:nvidia/NVIDIA-Nemotron-3-Super-120B-A12B-NVFP4',
                'fallback_model' => 'gemini',
                'tools' => ['web_search', 'compound_search', 'summarization', 'string_research'],
                'execution_preferences' => ['preferred_harness' => 'laravel_ai'],
            ]
        );

        $developer = Agent::query()->updateOrCreate(
            ['name' => 'Developer'],
            [
                'role' => 'Code Implementation Specialist',
                'description' => 'Reserved for Phase 1 sandbox execution.',
                'status' => 'active',
                'model' => 'hf:nvidia/NVIDIA-Nemotron-3-Super-120B-A12B-NVFP4',
                'fallback_model' => 'gemini',
                'tools' => ['code_editor', 'terminal'],
                'execution_preferences' => ['preferred_harness' => 'laravel_ai'],
            ]
        );

        $reviewer = Agent::query()->updateOrCreate(
            ['name' => 'Reviewer'],
            [
                'role' => 'Quality Assurance Specialist',
                'description' => 'Reserved for review workflows.',
                'status' => 'active',
                'model' => 'hf:nvidia/NVIDIA-Nemotron-3-Super-120B-A12B-NVFP4',
                'fallback_model' => 'gemini',
                'tools' => ['review'],
                'execution_preferences' => ['preferred_harness' => 'laravel_ai'],
            ]
        );

        $planner = Agent::query()->updateOrCreate(
            ['name' => 'Planner'],
            [
                'role' => 'Task Planning Specialist',
                'description' => 'Builds project plans and task breakdowns.',
                'status' => 'active',
                'model' => 'hf:nvidia/NVIDIA-Nemotron-3-Super-120B-A12B-NVFP4',
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
                'model' => 'hf:nvidia/NVIDIA-Nemotron-3-Super-120B-A12B-NVFP4',
                'fallback_model' => 'gemini',
                'tools' => ['testing'],
                'execution_preferences' => ['preferred_harness' => 'laravel_ai'],
            ]
        );

        $this->seedRuntime($clawra, 'laravel_ai', 'primary', 'laravel_class', CoordinatorRuntime::class, 'synthetic-laravel-ai', 'hf:nvidia/NVIDIA-Nemotron-3-Super-120B-A12B-NVFP4', 'gemini-laravel-ai', 'gemini-2.5-pro', true, ['coordination', 'project_state', 'provider_registry']);
        $this->seedRuntime($clawra, 'opencode', 'concierge', 'opencode_agent', 'general', 'openai-opencode-chatgpt', 'gpt-5.4', 'synthetic-opencode', 'deepseek-v3', false, ['chat', 'coordination']);

        $this->seedRuntime($planner, 'laravel_ai', 'planner-core', 'laravel_class', PlannerRuntime::class, 'synthetic-laravel-ai', 'hf:nvidia/NVIDIA-Nemotron-3-Super-120B-A12B-NVFP4', 'gemini-laravel-ai', 'gemini-2.5-pro', true, ['task_planning', 'project_breakdown']);
        $this->seedRuntime($planner, 'opencode', 'planner-sidecar', 'opencode_agent', 'plan', 'openai-opencode-chatgpt', 'gpt-5.4', 'synthetic-opencode', 'hf:nvidia/NVIDIA-Nemotron-3-Super-120B-A12B-NVFP4', false, ['task_planning']);

        $this->seedRuntime($researcher, 'laravel_ai', 'research-core', 'laravel_class', ResearchService::class, 'synthetic-laravel-ai', 'hf:nvidia/NVIDIA-Nemotron-3-Super-120B-A12B-NVFP4', 'gemini-laravel-ai', 'gemini-2.5-pro', true, ['web_search', 'compound_search', 'summarization', 'string_research']);
        $this->seedRuntime($researcher, 'opencode', 'research-sidecar', 'opencode_agent', 'explore', 'synthetic-opencode', 'deepseek-v3', 'synthetic-opencode', 'gemini-2.5-pro', false, ['web_search']);

        $this->seedRuntime($developer, 'laravel_ai', 'api-core', 'laravel_class', ResearchService::class, 'synthetic-laravel-ai', 'hf:nvidia/NVIDIA-Nemotron-3-Super-120B-A12B-NVFP4', 'genius-laravel-ai', 'gemini-2.5-pro', true, ['code_editor', 'terminal']);
        $this->seedRuntime($developer, 'opencode', 'builder', 'opencode_agent', 'build', 'openai-opencode-chatgpt', 'gpt-5.4', 'synthetic-opencode', 'deepseek-v3', false, ['code_editor', 'terminal']);
        $this->seedRuntime($developer, 'opencode', 'hack', 'opencode_agent', 'general', 'openai-opencode-chatgpt', 'gpt-5.4', 'synthetic-opencode', 'hf:nvidia/NVIDIA-Nemotron-3-Super-120B-A12B-NVFP4', false, ['code_editor', 'terminal']);

        $this->seedRuntime($reviewer, 'laravel_ai', 'review-core', 'laravel_class', ResearchService::class, 'synthetic-laravel-ai', 'hf:nvidia/NVIDIA-Nemotron-3-Super-120B-A12B-NVFP4', 'gemini-laravel-ai', 'gemini-2.5-pro', true, ['review']);
        $this->seedRuntime($reviewer, 'opencode', 'reviewer', 'opencode_agent', 'general', 'openai-opencode-chatgpt', 'gpt-5.4', 'synthetic-opencode', 'hf:nvidia/NVIDIA-Nemotron-3-Super-120B-A12B-NVFP4', true, ['review']);

        $this->seedRuntime($testWriter, 'laravel_ai', 'test-core', 'laravel_class', ResearchService::class, 'synthetic-laravel-ai', 'hf:nvidia/NVIDIA-Nemotron-3-Super-120B-A12B-NVFP4', 'gemini-laravel-ai', 'gemini-2.5-pro', true, ['testing']);
        $this->seedRuntime($testWriter, 'opencode', 'test-writer', 'opencode_agent', 'build', 'openai-opencode-chatgpt', 'gpt-5.4', 'synthetic-opencode', 'hf:nvidia/NVIDIA-Nemotron-3-Super-120B-A12B-NVFP4', false, ['testing']);
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
