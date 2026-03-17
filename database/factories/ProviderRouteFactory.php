<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Provider;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProviderRoute>
 */
class ProviderRouteFactory extends Factory
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
            'provider_id' => Provider::factory(),
            'name' => sprintf('%s-%s-%s', $this->faker->slug(), $harness, $this->faker->randomElement(['api-key', 'oauth'])),
            'harness' => $harness,
            'auth_mode' => $this->faker->randomElement(['api_key', 'chatgpt_oauth', 'provider_oauth']),
            'credential_type' => $this->faker->randomElement(['api_key', 'oauth_token', null]),
            'usage_snapshot' => ['requests_remaining' => 100],
            'rate_limits' => ['requests_per_window' => 100],
            'capability_tags' => ['chat'],
            'config' => [],
            'supports_tools' => true,
            'supports_structured_output' => $harness === 'laravel_ai',
            'priority' => 100,
            'status' => 'active',
        ];
    }
}
