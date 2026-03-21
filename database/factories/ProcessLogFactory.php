<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProcessLog>
 */
class ProcessLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'kind' => $this->faker->randomElement([
                'runtime.execution.started',
                'runtime.execution.completed',
                'runtime.execution.failed',
                'task.execution.completed',
                'task.execution.failed',
            ]),
            'status' => $this->faker->randomElement(['pending', 'success', 'failed']),
            'message' => $this->faker->sentence(),
            'context' => [],
        ];
    }
}
