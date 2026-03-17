<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Provider;
use App\Services\ProviderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProviderController extends Controller
{
    public function __construct(private ProviderService $providerService) {}

    /**
     * Display a listing of providers.
     */
    public function index(): JsonResponse
    {
        $providers = $this->providerService->getAllProviders();

        return response()->json($providers);
    }

    /**
     * Store a newly created provider.
     */
    public function store(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:subscription,hybrid,api-only,API-key-based,CLI-tool-based',
            'api_protocol' => 'required|string|in:OpenAI-compatible,Anthropic-compatible,native',
            'usage_snapshot' => 'nullable|array',
            'rate_limits' => 'nullable|array',
            'capability_tags' => 'required|array',
            'priority_preferences' => 'nullable|array',
            'status' => 'required|string|in:active,rate-limited,degraded,disabled',
        ]);

        $provider = $this->providerService->createProvider($validatedData);

        return response()->json($provider, 201);
    }

    /**
     * Display the specified provider.
     */
    public function show(int $id): JsonResponse
    {
        $provider = $this->providerService->getProviderById($id);
        if (! $provider) {
            return response()->json(['error' => 'Provider not found'], 404);
        }

        return response()->json($provider);
    }

    /**
     * Update the specified provider.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $provider = $this->providerService->getProviderById($id);
        if (! $provider) {
            return response()->json(['error' => 'Provider not found'], 404);
        }

        $validatedData = $request->validate([
            'name' => 'sometimes|string|max:255',
            'type' => 'sometimes|string|in:subscription,hybrid,api-only,API-key-based,CLI-tool-based',
            'api_protocol' => 'sometimes|string|in:OpenAI-compatible,Anthropic-compatible,native',
            'usage_snapshot' => 'nullable|array',
            'rate_limits' => 'nullable|array',
            'capability_tags' => 'sometimes|array',
            'priority_preferences' => 'nullable|array',
            'status' => 'sometimes|string|in:active,rate-limited,degraded,disabled',
        ]);

        $updated = $this->providerService->updateProvider($id, $validatedData);
        if ($updated) {
            return response()->json($this->providerService->getProviderById($id));
        }

        return response()->json(['error' => 'Failed to update provider'], 500);
    }

    /**
     * Remove the specified provider.
     */
    public function destroy(int $id): JsonResponse
    {
        $provider = $this->providerService->getProviderById($id);
        if (! $provider) {
            return response()->json(['error' => 'Provider not found'], 404);
        }

        $deleted = $this->providerService->deleteProvider($id);
        if ($deleted) {
            return response()->json(['message' => 'Provider deleted successfully']);
        }

        return response()->json(['error' => 'Failed to delete provider'], 500);
    }

    /**
     * Get active providers.
     */
    public function getActive(): JsonResponse
    {
        $providers = $this->providerService->getActiveProviders();

        return response()->json($providers);
    }

    /**
     * Get providers by capability.
     */
    public function getByCapability(string $capability): JsonResponse
    {
        $providers = $this->providerService->getProvidersByCapability($capability);

        return response()->json($providers);
    }
}
