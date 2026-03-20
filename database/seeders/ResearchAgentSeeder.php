<?php

declare(strict_types=1);

use App\Models\Agent;
use App\Models\AgentRuntime;
use App\Models\Provider;
use App\Models\ProviderModel;
use App\Models\ProviderRoute;
use Illuminate\Database\Seeder;

class ResearchAgentSeeder extends Seeder
{
    public function run(): void
    {
        // Setup synthetic-native provider for research
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
                    'embeddings' => 50, // free, no limit impact
                ],
            ]
        );

        $syntheticRoute = ProviderRoute::query()->updateOrCreate(
            ['name' => 'synthetic-laravel-ai'],
            [
                'provider_id' => $synthetic->id,
                'name' => 'synthetic-laravel-ai',
                'endpoint_urls' => [
                    'chat' => 'https://api.synthetic.new/anthropic/v1/messages',
                    'search' => 'https://api.synthetic.new/v2/search',
                ],
                'sync_capabilities' => [
                    'usage' => true,
                ],
                'async_capabilities' => false,
            ]
        );

        // Create NVIDIA Nemotron model for deep research
        ProviderModel::query()->updateOrCreate(
            [
                'provider_route_id' => $syntheticRoute->id,
                'name' => 'hf:nvidia/NVIDIA-Nemotron-3-Super-120B-A12B-NVFP4',
            ],
            [
                'name' => 'hf:nvidia/NVIDIA-Nemotron-3-Super-120B-A12B-NVFP4',
                'external_name' => 'NVIDIA Nemotron 3-Super 120B',
                'metadata' => [
                    'parameters' => 120_000_000_000,
                    'context_window' => 256_000,
                    'finish_reasons' => ['STOP', 'LENGTH', 'ERROR'],
                    'license' => 'proprietary',
                ],
                'capabilities' => [
                    'reasoning' => true,
                    'web-search' => true,
                    'json-schema' => true,
                    'code-generation' => true,
                    'summarization' => true,
                ],
                'priority_preferences' => [
                    'reasoning' => 99,
                    'summarization' => 98,
                    'json-schema' => 97,
                ],
            ]
        );

        // Update Researcher agent with Nemotron model
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

        // Researcher's primary runtime - using Nemotron for deep reasoning
        $this->seedRuntime(
            agent: $researcher,
            harness: 'laravel_ai',
            name: 'research-core',
            runtimeType: 'laravel_class',
            runtimeRef: 'App\\Services\\ResearchService',
            routeName: 'synthetic-laravel-ai',
            modelName: 'hf:nvidia/NVIDIA-Nemotron-3-Super-120B-A12B-NVFP4',
            fallbackRouteName: null,
            fallbackModelName: null,
            isDefault: true,
            tools: ['web_search', 'compound_search', 'summarization', 'string_research'],
        );
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
