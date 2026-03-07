<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Provider;

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
            'type' => $this->faker->randomElement(['API-key-based', 'CLI-tool-based']),
            'api_protocol' => $this->faker->randomElement(['OpenAI-compatible', 'Anthropic-compatible', 'native']),
            'usage_snapshot' => null,
            'rate_limits' => null,
            'capability_tags' => null,
            'priority_preferences' => null,
            'status' => $this->faker->randomElement(['active', 'rate-limited', 'degraded', 'disabled']),
        ];
    }
}
