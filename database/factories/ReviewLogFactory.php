<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Agent;
use App\Models\ReviewLog;
use App\Models\Subtask;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ReviewLog>
 */
class ReviewLogFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ReviewLog::class;

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
            'reviewer_agent_id' => Agent::factory(),
            'decision' => $this->faker->randomElement(['approved', 'rejected', 'needs_revision']),
            'diff_content' => $this->faker->sentence(),
            'comments' => $this->faker->paragraph(),
            'created_at' => now(),
        ];
    }
}
