<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Agent;
use App\Models\AgentRuntime;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class AgentService
{
    /**
     * Get all agents.
     */
    public function getAllAgents(): Collection
    {
        return $this->query()->orderBy('name')->get();
    }

    public function getAssignableAgents(): Collection
    {
        return $this->getAllAgents()
            ->reject(fn (Agent $agent): bool => $agent->name === 'Clawra')
            ->values();
    }

    public function getAgentByName(string $name): ?Agent
    {
        return $this->query()->where('name', $name)->first();
    }

    /**
     * Get an agent by ID.
     */
    public function getAgentById(int $id): ?Agent
    {
        return $this->query()->find($id);
    }

    public function getRuntimeForAgent(string $name, string $harness = 'laravel_ai'): ?AgentRuntime
    {
        $agent = $this->getAgentByName($name);

        if (! $agent instanceof Agent) {
            return null;
        }

        return $agent->runtimes
            ->where('harness', $harness)
            ->where('status', 'active')
            ->sortByDesc('is_default')
            ->first();
    }

    public function getPreferredRuntimeForAgent(string $name): ?AgentRuntime
    {
        $agent = $this->getAgentByName($name);

        if (! $agent instanceof Agent) {
            return null;
        }

        return $agent->runtimes
            ->where('status', 'active')
            ->sortByDesc('is_default')
            ->first();
    }

    /**
     * @return array{provider: string, model: ?string, fallback_provider: ?string, fallback_model: ?string, runtime: ?AgentRuntime}
     */
    public function getLaravelAiConfigForAgent(
        string $name,
        string $defaultProvider,
        ?string $defaultFallback = null,
        ?string $defaultModel = null,
        ?string $defaultFallbackModel = null,
    ): array {
        $runtime = $this->getRuntimeForAgent($name, 'laravel_ai');

        if (! $runtime instanceof AgentRuntime) {
            return [
                'provider' => $defaultProvider,
                'model' => $defaultModel,
                'fallback_provider' => $defaultFallback,
                'fallback_model' => $defaultFallbackModel,
                'runtime' => null,
            ];
        }

        return [
            'provider' => $runtime->route?->provider?->name ?? $defaultProvider,
            'model' => $runtime->model?->external_name ?? $runtime->model?->name ?? $runtime->route?->provider?->name ?? $defaultModel ?? $defaultProvider,
            'fallback_provider' => $runtime->fallbackRoute?->provider?->name ?? $defaultFallback,
            'fallback_model' => $runtime->fallbackModel?->external_name ?? $runtime->fallbackModel?->name ?? $runtime->fallbackRoute?->provider?->name ?? $defaultFallbackModel ?? $defaultFallback,
            'runtime' => $runtime,
        ];
    }

    /**
     * Create a new agent.
     */
    public function createAgent(array $data): Agent
    {
        return Agent::query()->create($data);
    }

    /**
     * Update an agent.
     */
    public function updateAgent(int $id, array $data): bool
    {
        $agent = $this->getAgentById($id);

        if ($agent instanceof Agent) {
            return $agent->update($data);
        }

        return false;
    }

    /**
     * Delete an agent.
     */
    public function deleteAgent(int $id): bool
    {
        $agent = $this->getAgentById($id);

        if ($agent instanceof Agent) {
            return (bool) $agent->delete();
        }

        return false;
    }

    /**
     * Get agents by tool capability.
     */
    public function getAgentsByTool(string $tool): Collection
    {
        return $this->query()->where('tools', 'like', '%'.$tool.'%')->get();
    }

    protected function query(): Builder
    {
        return Agent::query()->with([
            'runtimes.route.provider',
            'runtimes.model',
            'runtimes.fallbackRoute.provider',
            'runtimes.fallbackModel',
            'defaultRuntime.route.provider',
            'defaultRuntime.model',
            'defaultRuntime.fallbackRoute.provider',
            'defaultRuntime.fallbackModel',
        ]);
    }
}
