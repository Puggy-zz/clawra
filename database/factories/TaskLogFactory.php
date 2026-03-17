<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Agent;
use App\Models\Subtask;
use App\Models\Task;
use App\Models\TaskLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TaskLog>
 */
class TaskLogFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = TaskLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'task_id' => Task::factory(),
            'subtask_id' => Subtask::factory(),
            'agent_id' => Agent::factory(),
            'log_type' => $this->faker->randomElement(['info', 'warning', 'error', 'debug']),
            'content' => $this->faker->sentence(),
            'metadata' => [],
            'created_at' => now(),
        ];
    }
}
