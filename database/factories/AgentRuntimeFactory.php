<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Agent;
use App\Models\ProviderModel;
use App\Models\ProviderRoute;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AgentRuntime>
 */
class AgentRuntimeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $harness = $this->faker->randomElement(['laravel_ai', 'opencode', 'codex']);

        return [
            'agent_id' => Agent::factory(),
            'provider_route_id' => ProviderRoute::factory(),
            'provider_model_id' => ProviderModel::factory(),
            'fallback_provider_route_id' => null,
            'fallback_provider_model_id' => null,
            'name' => sprintf('%s-%s', $this->faker->slug(), $harness),
            'harness' => $harness,
            'runtime_type' => $this->faker->randomElement(['laravel_class', 'opencode_agent', 'codex_subagent']),
            'runtime_ref' => $harness === 'laravel_ai' ? 'App\\Ai\\Agents\\ExampleAgent' : 'general',
            'description' => $this->faker->sentence(),
            'tools' => ['chat'],
            'config' => [],
            'is_default' => true,
            'saves_documents' => false,
            'sandboxed' => false,
            'status' => 'active',
        ];
    }
}
