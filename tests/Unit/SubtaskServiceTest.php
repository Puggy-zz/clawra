<?php

declare(strict_types=1);

use App\Models\Project;
use App\Models\Subtask;
use App\Models\Task;
use App\Models\Workflow;
use App\Services\SubtaskService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

it('creates missing agent records when building subtasks', function () {
    $project = Project::factory()->create();
    $workflow = Workflow::factory()->create();
    $task = Task::factory()->create([
        'project_id' => $project->id,
        'workflow_id' => $workflow->id,
    ]);

    $service = new SubtaskService;

    $subtasks = $service->createSubtasksForTask($task->id, [
        ['name' => 'Initialize'],
        ['name' => 'Research'],
        ['name' => 'Review'],
    ]);

    expect($subtasks)->toHaveCount(3)
        ->and(Subtask::query()->count())->toBe(3)
        ->and(App\Models\Agent::query()->where('name', 'Planner')->exists())->toBeTrue()
        ->and(App\Models\Agent::query()->where('name', 'Researcher')->exists())->toBeTrue()
        ->and(App\Models\Agent::query()->where('name', 'Reviewer')->exists())->toBeTrue();
});
