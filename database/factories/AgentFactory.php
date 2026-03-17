<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Agent;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Agent>
 */
class AgentFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Agent::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'role' => $this->faker->jobTitle(),
            'description' => $this->faker->paragraph(),
            'status' => 'active',
            'model' => $this->faker->randomElement(['synthetic', 'gemini']),
            'fallback_model' => $this->faker->randomElement(['synthetic', 'gemini']),
            'tools' => [],
            'execution_preferences' => [],
        ];
    }
}
