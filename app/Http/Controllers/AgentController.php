<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Services\AgentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentController extends Controller
{
    public function __construct(private AgentService $agentService) {}

    /**
     * Display a listing of agents.
     */
    public function index(): JsonResponse
    {
        $agents = $this->agentService->getAllAgents();

        return response()->json($agents);
    }

    /**
     * Store a newly created agent.
     */
    public function store(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'role' => 'required|string|max:255',
            'description' => 'nullable|string',
            'model' => 'required|string|max:255',
            'fallback_model' => 'required|string|max:255',
            'tools' => 'required|array',
        ]);

        $agent = $this->agentService->createAgent($validatedData);

        return response()->json($agent, 201);
    }

    /**
     * Display the specified agent.
     */
    public function show(int $id): JsonResponse
    {
        $agent = $this->agentService->getAgentById($id);
        if (! $agent) {
            return response()->json(['error' => 'Agent not found'], 404);
        }

        return response()->json($agent);
    }

    /**
     * Update the specified agent.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $agent = $this->agentService->getAgentById($id);
        if (! $agent) {
            return response()->json(['error' => 'Agent not found'], 404);
        }

        $validatedData = $request->validate([
            'name' => 'sometimes|string|max:255',
            'role' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'model' => 'sometimes|string|max:255',
            'fallback_model' => 'sometimes|string|max:255',
            'tools' => 'sometimes|array',
        ]);

        $updated = $this->agentService->updateAgent($id, $validatedData);
        if ($updated) {
            return response()->json($this->agentService->getAgentById($id));
        }

        return response()->json(['error' => 'Failed to update agent'], 500);
    }

    /**
     * Remove the specified agent.
     */
    public function destroy(int $id): JsonResponse
    {
        $agent = $this->agentService->getAgentById($id);
        if (! $agent) {
            return response()->json(['error' => 'Agent not found'], 404);
        }

        $deleted = $this->agentService->deleteAgent($id);
        if ($deleted) {
            return response()->json(['message' => 'Agent deleted successfully']);
        }

        return response()->json(['error' => 'Failed to delete agent'], 500);
    }

    /**
     * Get agents by tool capability.
     */
    public function getByTool(string $tool): JsonResponse
    {
        $agents = $this->agentService->getAgentsByTool($tool);

        return response()->json($agents);
    }
}
