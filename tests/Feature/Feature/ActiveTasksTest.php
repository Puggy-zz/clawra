<?php

declare(strict_types=1);

use App\Livewire\ActiveTasks;
use App\Models\ProcessLog;
use App\Models\Task;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders active and in-progress tasks', function () {
    $task = Task::factory()->create(['status' => 'in-progress', 'name' => 'My Test Task']);

    Livewire::test(ActiveTasks::class)->assertSee('My Test Task');
});

it('shows task detail panel when task is selected', function () {
    $task = Task::factory()->create(['status' => 'in-progress', 'description' => 'Build something cool']);

    Livewire::test(ActiveTasks::class)
        ->call('selectTask', $task->id)
        ->assertSet('selectedTaskId', $task->id)
        ->assertSee('Build something cool');
});

it('deselects task when same task is clicked again', function () {
    $task = Task::factory()->create(['status' => 'in-progress']);

    Livewire::test(ActiveTasks::class)
        ->call('selectTask', $task->id)
        ->assertSet('selectedTaskId', $task->id)
        ->call('selectTask', $task->id)
        ->assertSet('selectedTaskId', null);
});

it('shows process logs for selected task', function () {
    $task = Task::factory()->create(['status' => 'completed']);
    ProcessLog::factory()->create([
        'task_id' => $task->id,
        'kind' => 'runtime.execution.completed',
        'message' => 'Runtime execution completed.',
        'context' => ['text' => 'I wrote the code!'],
    ]);

    Livewire::test(ActiveTasks::class)
        ->call('selectTask', $task->id)
        ->assertSee('I wrote the code!');
});

it('excludes completed tasks older than 24 hours', function () {
    $old = Task::factory()->create(['status' => 'completed', 'updated_at' => now()->subDays(2)]);
    $recent = Task::factory()->create(['status' => 'completed', 'updated_at' => now()->subMinutes(10)]);

    Livewire::test(ActiveTasks::class)
        ->assertDontSee($old->name)
        ->assertSee($recent->name);
});
