<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Workflow;
use App\Services\WorkflowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WorkflowController extends Controller
{
    public function __construct(private WorkflowService $workflowService) {}

    /**
     * Display a listing of workflows.
     */
    public function index(): JsonResponse
    {
        $workflows = $this->workflowService->getAllWorkflows();

        return response()->json($workflows);
    }

    /**
     * Store a newly created workflow.
     */
    public function store(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'steps' => 'required|array',
        ]);

        $workflow = $this->workflowService->createWorkflow($validatedData);

        return response()->json($workflow, 201);
    }

    /**
     * Display the specified workflow.
     */
    public function show(int $id): JsonResponse
    {
        $workflow = $this->workflowService->getWorkflowById($id);
        if (! $workflow) {
            return response()->json(['error' => 'Workflow not found'], 404);
        }

        return response()->json($workflow);
    }

    /**
     * Update the specified workflow.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $workflow = $this->workflowService->getWorkflowById($id);
        if (! $workflow) {
            return response()->json(['error' => 'Workflow not found'], 404);
        }

        $validatedData = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'steps' => 'sometimes|array',
        ]);

        $updated = $this->workflowService->updateWorkflow($id, $validatedData);
        if ($updated) {
            return response()->json($this->workflowService->getWorkflowById($id));
        }

        return response()->json(['error' => 'Failed to update workflow'], 500);
    }

    /**
     * Remove the specified workflow.
     */
    public function destroy(int $id): JsonResponse
    {
        $workflow = $this->workflowService->getWorkflowById($id);
        if (! $workflow) {
            return response()->json(['error' => 'Workflow not found'], 404);
        }

        $deleted = $this->workflowService->deleteWorkflow($id);
        if ($deleted) {
            return response()->json(['message' => 'Workflow deleted successfully']);
        }

        return response()->json(['error' => 'Failed to delete workflow'], 500);
    }

    /**
     * Get workflow steps.
     */
    public function getSteps(int $id): JsonResponse
    {
        $steps = $this->workflowService->getWorkflowSteps($id);

        return response()->json($steps);
    }
}
