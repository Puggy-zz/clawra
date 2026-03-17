<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\HeartbeatLog;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\HeartbeatLog>
 */
class HeartbeatLogFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = HeartbeatLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'timestamp' => now(),
            'decisions' => [],
            'tasks_queued' => [],
            'provider_status' => [],
            'created_at' => now(),
        ];
    }
}
