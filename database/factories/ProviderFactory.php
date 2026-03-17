<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Provider;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Provider>
 */
class ProviderFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Provider::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Test Provider',
            'vendor' => $this->faker->company(),
            'type' => $this->faker->randomElement(['subscription', 'hybrid', 'api-only']),
            'api_protocol' => $this->faker->randomElement(['OpenAI-compatible', 'Anthropic-compatible', 'native']),
            'usage_snapshot' => null,
            'rate_limits' => null,
            'capability_tags' => null,
            'priority_preferences' => null,
            'config' => [],
            'status' => $this->faker->randomElement(['active', 'rate-limited', 'degraded', 'disabled']),
        ];
    }
}
