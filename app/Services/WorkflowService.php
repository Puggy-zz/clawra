<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Workflow;
use Illuminate\Database\Eloquent\Collection;

class WorkflowService
{
    public function getAllWorkflows(): Collection
    {
        return Workflow::query()->orderBy('name')->get();
    }

    public function getWorkflowById(int $id): ?Workflow
    {
        return Workflow::find($id);
    }

    public function createWorkflow(array $data): Workflow
    {
        return Workflow::query()->create($data);
    }

    public function updateWorkflow(int $id, array $data): bool
    {
        $workflow = $this->getWorkflowById($id);

        return $workflow instanceof Workflow ? $workflow->update($data) : false;
    }

    public function deleteWorkflow(int $id): bool
    {
        $workflow = $this->getWorkflowById($id);

        return $workflow instanceof Workflow ? (bool) $workflow->delete() : false;
    }

    public function getWorkflowSteps(int $workflowId): array
    {
        return $this->getWorkflowById($workflowId)?->steps ?? [];
    }

    public function getDefaultWorkflowForType(string $taskType): Workflow
    {
        $workflowName = match ($taskType) {
            'research' => 'Research Brief Workflow',
            'planning' => 'Planning Workflow',
            default => 'Coordinator Intake Workflow',
        };

        return Workflow::query()->firstOrCreate(
            ['name' => $workflowName],
            [
                'description' => sprintf('Auto-generated default workflow for %s requests.', $taskType),
                'steps' => $this->defaultStepsForType($taskType),
            ]
        );
    }

    protected function defaultStepsForType(string $taskType): array
    {
        return match ($taskType) {
            'research' => [
                ['name' => 'Research', 'description' => 'Collect source material'],
                ['name' => 'Analyze', 'description' => 'Extract findings and risks'],
                ['name' => 'Document', 'description' => 'Summarize the research output'],
            ],
            'planning' => [
                ['name' => 'Initialize', 'description' => 'Capture requirements and scope'],
                ['name' => 'Plan', 'description' => 'Create a structured implementation plan'],
                ['name' => 'Finalize', 'description' => 'Store the approved next actions'],
            ],
            default => [
                ['name' => 'Initialize', 'description' => 'Capture the request'],
                ['name' => 'Research', 'description' => 'Gather missing context'],
                ['name' => 'Finalize', 'description' => 'Prepare the next action'],
            ],
        };
    }
}
