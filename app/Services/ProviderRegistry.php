<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Provider;
use Illuminate\Support\Collection;

class ProviderRegistry
{
    /**
     * Get all active providers.
     */
    public function getActiveProviders(): Collection
    {
        return Provider::where('status', 'active')->get();
    }

    /**
     * Get a provider by name.
     */
    public function getProvider(string $name): ?Provider
    {
        return Provider::where('name', $name)->first();
    }

    /**
     * Update provider usage snapshot.
     */
    public function updateUsageSnapshot(string $providerName, array $usageData): bool
    {
        $provider = $this->getProvider($providerName);
        
        if (!$provider) {
            return false;
        }
        
        $provider->usage_snapshot = $usageData;
        return $provider->save();
    }

    /**
     * Update provider rate limits.
     */
    public function updateRateLimits(string $providerName, array $rateLimits): bool
    {
        $provider = $this->getProvider($providerName);
        
        if (!$provider) {
            return false;
        }
        
        $provider->rate_limits = $rateLimits;
        return $provider->save();
    }

    /**
     * Get the best provider for a specific capability.
     */
    public function getBestProviderForCapability(string $capability): ?Provider
    {
        // This is a simplified implementation
        // In a real implementation, you would consider:
        // - Provider status (active, rate-limited, etc.)
        // - Capability tags
        // - Priority preferences
        // - Current usage vs rate limits
        // - Fallback options
        
        return Provider::where('status', 'active')
            ->whereJsonContains('capability_tags', $capability)
            ->orderBy('priority_preferences->' . $capability, 'desc')
            ->first();
    }

    /**
     * Check if a provider is rate limited.
     */
    public function isProviderRateLimited(string $providerName): bool
    {
        $provider = $this->getProvider($providerName);
        
        if (!$provider) {
            return true; // Treat missing provider as rate limited
        }
        
        return $provider->status === 'rate-limited';
    }

    /**
     * Get all providers with their current status.
     */
    public function getProviderStatusReport(): array
    {
        $providers = Provider::all();
        $report = [];
        
        foreach ($providers as $provider) {
            $report[] = [
                'name' => $provider->name,
                'status' => $provider->status,
                'usage' => $provider->usage_snapshot,
                'capabilities' => $provider->capability_tags,
            ];
        }
        
        return $report;
    }
}
