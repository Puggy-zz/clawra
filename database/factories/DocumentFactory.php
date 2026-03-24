<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Document;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Document>
 */
class DocumentFactory extends Factory
{
    protected $model = Document::class;

    public function definition(): array
    {
        $title = $this->faker->sentence(5);

        return [
            'title' => $title,
            'file_name' => \Illuminate\Support\Str::slug($title).'.md',
            'content' => $this->faker->paragraphs(3, true),
            'file_type' => $this->faker->randomElement(['md', 'txt']),
            'project_id' => null,
            'task_id' => null,
            'access_level' => 'internal',
            'metadata' => [],
        ];
    }
}
