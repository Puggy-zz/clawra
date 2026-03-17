<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Project;
use App\Models\Sandbox;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Sandbox>
 */
class SandboxFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Sandbox::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'name' => $this->faker->word(),
            'status' => $this->faker->randomElement(['active', 'inactive', 'archived']),
            'path' => $this->faker->filePath(),
        ];
    }
}
