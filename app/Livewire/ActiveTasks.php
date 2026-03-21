<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Agent;
use App\Models\ProcessLog;
use App\Models\Project;
use App\Models\Task;
use App\Models\Workflow;
use Illuminate\View\View;
use Livewire\Component;

class ActiveTasks extends Component
{
    public ?int $selectedTaskId = null;

    public bool $showCreateForm = false;

    public function selectTask(int $id): void
    {
        $this->selectedTaskId = ($this->selectedTaskId === $id) ? null : $id;
    }

    public function toggleCreateForm(): void
    {
        $this->showCreateForm = ! $this->showCreateForm;
    }

    public function render(): View
    {
        $tasks = Task::query()
            ->with(['project', 'workflow', 'recommendedAgent'])
            ->where(function ($q) {
                $q->whereIn('status', ['pending', 'in-progress'])
                    ->orWhere(fn ($q2) => $q2
                        ->whereIn('status', ['completed', 'failed'])
                        ->where('updated_at', '>=', now()->subHours(24)));
            })
            ->orderByRaw("CASE status WHEN 'in-progress' THEN 0 WHEN 'pending' THEN 1 WHEN 'failed' THEN 2 ELSE 3 END")
            ->orderBy('priority')
            ->orderByDesc('updated_at')
            ->limit(25)
            ->get();

        $selectedTask = null;
        $taskLogs = collect();

        if ($this->selectedTaskId !== null) {
            $selectedTask = Task::query()
                ->with(['project', 'workflow', 'recommendedAgent'])
                ->find($this->selectedTaskId);

            if ($selectedTask) {
                $taskLogs = ProcessLog::query()
                    ->with(['agent', 'agentRuntime', 'externalSession'])
                    ->where('task_id', $this->selectedTaskId)
                    ->orderBy('created_at')
                    ->limit(50)
                    ->get();
            }
        }

        $projects = Project::query()->orderBy('name')->get();
        $workflows = Workflow::query()->orderBy('name')->get();
        $agents = Agent::query()->orderBy('name')->get();

        return view('livewire.active-tasks', compact(
            'tasks', 'selectedTask', 'taskLogs', 'projects', 'workflows', 'agents'
        ));
    }
}
