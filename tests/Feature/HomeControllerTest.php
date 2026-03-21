<?php

declare(strict_types=1);

use App\Models\Document;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('renders the home page successfully', function () {
    $this->get('/')->assertSuccessful();
});

it('passes draft tasks to the view', function () {
    $project = Project::factory()->create(['name' => 'Clawra Core', 'status' => 'active']);

    Task::factory()->create([
        'project_id' => $project->id,
        'name' => 'Suggestion: Write unit tests',
        'status' => 'draft',
        'description' => 'Add test coverage for the new dispatch service.',
    ]);

    Task::factory()->create([
        'project_id' => $project->id,
        'name' => 'Active Task',
        'status' => 'pending',
    ]);

    $response = $this->get('/');

    $response->assertSuccessful();
    $response->assertViewHas('draftTasks', function ($draftTasks) {
        return $draftTasks->count() === 1
            && $draftTasks->first()->name === 'Suggestion: Write unit tests';
    });
});

it('does not include non-draft tasks in draft tasks', function () {
    $project = Project::factory()->create(['status' => 'active']);

    Task::factory()->create(['project_id' => $project->id, 'status' => 'pending']);
    Task::factory()->create(['project_id' => $project->id, 'status' => 'in-progress']);
    Task::factory()->create(['project_id' => $project->id, 'status' => 'completed']);

    $response = $this->get('/');

    $response->assertViewHas('draftTasks', function ($draftTasks) {
        return $draftTasks->isEmpty();
    });
});

it('passes recent documents to the view', function () {
    $project = Project::factory()->create(['status' => 'active']);

    Document::factory()->create([
        'project_id' => $project->id,
        'title' => 'Research: Competitor Analysis',
        'content' => '# Competitor Analysis\n\nThis is the content.',
        'file_type' => 'md',
    ]);

    $response = $this->get('/');

    $response->assertSuccessful();
    $response->assertViewHas('recentDocuments', function ($docs) {
        return $docs->count() === 1
            && $docs->first()->title === 'Research: Competitor Analysis';
    });
});

it('limits recent documents to 5', function () {
    Document::factory()->count(8)->create(['file_type' => 'md']);

    $response = $this->get('/');

    $response->assertViewHas('recentDocuments', function ($docs) {
        return $docs->count() === 5;
    });
});

it('shows suggestions panel when draft tasks exist', function () {
    $project = Project::factory()->create(['status' => 'active']);

    Task::factory()->create([
        'project_id' => $project->id,
        'name' => 'Suggestion: Refactor auth service',
        'status' => 'draft',
    ]);

    $this->get('/')
        ->assertSuccessful()
        ->assertSee('Suggestions')
        ->assertSee('Refactor auth service');
});

it('hides suggestions panel when no draft tasks exist', function () {
    $this->get('/')
        ->assertSuccessful()
        ->assertDontSee('id="suggestions-panel"', false);
});
