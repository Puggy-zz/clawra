<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Project;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProjectConversation>
 */
class ProjectConversationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'title' => $this->faker->sentence(3),
            'purpose' => $this->faker->randomElement(['main', 'bugfix', 'experiment']),
            'status' => 'active',
            'is_default' => false,
            'laravel_ai_conversation_id' => null,
            'state_document' => [
                'pending_task_draft' => null,
                'summary' => null,
            ],
            'last_message_at' => null,
        ];
    }
}
