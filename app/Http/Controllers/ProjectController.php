<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\ProjectService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function __construct(private ProjectService $projectService) {}

    /**
     * Display a listing of projects.
     */
    public function index(): JsonResponse
    {
        $projects = $this->projectService->getAllProjects();

        return response()->json($projects);
    }

    /**
     * Store a newly created project.
     */
    public function store(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'goals' => 'nullable|string',
            'status' => 'required|string|in:active,paused,blocked,complete',
            'state_document' => 'nullable|array',
            'current_intent' => 'nullable|string',
        ]);

        $project = $this->projectService->createProject($validatedData);

        return response()->json($project, 201);
    }

    /**
     * Display the specified project.
     */
    public function show(int $id): JsonResponse
    {
        $project = $this->projectService->getProjectById($id);
        if (! $project) {
            return response()->json(['error' => 'Project not found'], 404);
        }

        return response()->json($project);
    }

    /**
     * Update the specified project.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $project = $this->projectService->getProjectById($id);
        if (! $project) {
            return response()->json(['error' => 'Project not found'], 404);
        }

        $validatedData = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'goals' => 'nullable|string',
            'status' => 'sometimes|string|in:active,paused,blocked,complete',
            'state_document' => 'nullable|array',
            'current_intent' => 'nullable|string',
        ]);

        $updated = $this->projectService->updateProject($id, $validatedData);
        if ($updated) {
            return response()->json($this->projectService->getProjectById($id));
        }

        return response()->json(['error' => 'Failed to update project'], 500);
    }

    /**
     * Remove the specified project.
     */
    public function destroy(int $id): JsonResponse
    {
        $project = $this->projectService->getProjectById($id);
        if (! $project) {
            return response()->json(['error' => 'Project not found'], 404);
        }

        $deleted = $this->projectService->deleteProject($id);
        if ($deleted) {
            return response()->json(['message' => 'Project deleted successfully']);
        }

        return response()->json(['error' => 'Failed to delete project'], 500);
    }
}
