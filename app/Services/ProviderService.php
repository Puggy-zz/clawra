<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Provider;
use App\Models\ProviderRoute;
use Illuminate\Support\Collection;

class ProviderService
{
    public function __construct(protected ProviderRegistry $providerRegistry) {}

    /**
     * Get all providers.
     */
    public function getAllProviders(): Collection
    {
        return $this->providerRegistry->getProviders()->values();
    }

    public function getAllRoutes(?string $harness = null): Collection
    {
        return $this->providerRegistry->getRoutes($harness);
    }

    public function getRoutesForProvider(string $providerName, ?string $harness = null): Collection
    {
        return $this->getAllRoutes($harness)->filter(function (ProviderRoute $route) use ($providerName): bool {
            return $route->provider->name === $providerName;
        })->values();
    }

    public function getModelsForRoute(ProviderRoute $route): Collection
    {
        return $route->models->sortBy('priority')->values();
    }

    /**
     * Get a provider by ID.
     */
    public function getProviderById(int $id): ?Provider
    {
        return $this->providerRegistry->getProvider($id);
    }

    /**
     * Create a new provider.
     */
    public function createProvider(array $data): Provider
    {
        return $this->providerRegistry->addProvider($data);
    }

    /**
     * Update a provider.
     */
    public function updateProvider(int $id, array $data): bool
    {
        $provider = $this->getProviderById($id);
        if ($provider) {
            return $provider->update($data);
        }

        return false;
    }

    /**
     * Delete a provider.
     */
    public function deleteProvider(int $id): bool
    {
        return $this->providerRegistry->removeProvider($id);
    }

    /**
     * Get active providers.
     */
    public function getActiveProviders(): Collection
    {
        return $this->providerRegistry->getActiveProviders();
    }

    /**
     * Get providers by capability.
     */
    public function getProvidersByCapability(string $capability): Collection
    {
        return $this->providerRegistry->getProvidersByCapability($capability)->values();
    }

    /**
     * Get the best provider for a specific capability.
     */
    public function getBestProviderForCapability(string $capability): ?Provider
    {
        return $this->providerRegistry->getBestProviderForCapability($capability);
    }

    public function getBestRouteForCapability(string $capability, string $harness = 'laravel_ai'): ?ProviderRoute
    {
        return $this->providerRegistry->getBestRouteForCapability($capability, harness: $harness);
    }

    /**
     * Set the active provider.
     */
    public function setActiveProvider(int $providerId): bool
    {
        return $this->providerRegistry->setActiveProvider($providerId);
    }

    /**
     * Get the active provider.
     */
    public function getActiveProvider(): ?Provider
    {
        return $this->providerRegistry->getActiveProvider();
    }
}
