<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\ProviderRoute;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProviderModel>
 */
class ProviderModelFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = $this->faker->randomElement(['gpt-5.4', 'gemini-2.5-pro', 'deepseek-v3']);

        return [
            'provider_route_id' => ProviderRoute::factory(),
            'name' => $name,
            'external_name' => $name,
            'capabilities' => ['chat'],
            'config' => [],
            'context_window' => 200000,
            'priority' => 100,
            'is_default' => true,
            'status' => 'active',
        ];
    }
}
