<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Agent;
use App\Models\Subtask;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Subtask>
 */
class SubtaskFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Subtask::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'agent_id' => Agent::factory(),
            'name' => $this->faker->sentence(4),
            'inputs' => [],
            'outputs' => [],
            'status' => $this->faker->randomElement(['pending', 'in-progress', 'completed', 'failed']),
            'order' => $this->faker->numberBetween(1, 10),
        ];
    }
}
