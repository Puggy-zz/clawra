<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Provider;
use App\Models\ProviderModel;
use App\Models\ProviderRoute;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ProviderRegistry
{
    protected Collection $providers;

    protected Collection $routes;

    public function __construct()
    {
        $this->refresh();
    }

    public function refresh(): Collection
    {
        $this->providers = Provider::query()
            ->with(['routes.models'])
            ->orderBy('name')
            ->get()
            ->keyBy('id');

        $this->routes = $this->providers
            ->flatMap(fn (Provider $provider): Collection => $provider->routes)
            ->keyBy('id');

        return $this->providers;
    }

    public function getProviders(): Collection
    {
        return $this->refresh();
    }

    public function getProvider(int $id): ?Provider
    {
        return $this->getProviders()->get($id);
    }

    public function getProviderByName(string $name): ?Provider
    {
        return $this->getProviders()->first(fn (Provider $provider): bool => Str::lower($provider->name) === Str::lower($name));
    }

    public function getRoutes(?string $harness = null): Collection
    {
        $routes = $this->refreshRoutes();

        if ($harness === null) {
            return $routes->values();
        }

        return $routes->filter(fn (ProviderRoute $route): bool => $route->harness === $harness)->values();
    }

    public function getRoute(int $id): ?ProviderRoute
    {
        return $this->refreshRoutes()->get($id);
    }

    public function getRouteByName(string $name): ?ProviderRoute
    {
        return $this->refreshRoutes()->first(fn (ProviderRoute $route): bool => Str::lower($route->name) === Str::lower($name));
    }

    public function getProviderRouteForHarness(string $providerName, string $harness = 'laravel_ai'): ?ProviderRoute
    {
        return $this->getRoutes($harness)
            ->filter(fn (ProviderRoute $route): bool => Str::lower($route->provider->name) === Str::lower($providerName))
            ->sortBy('priority')
            ->first();
    }

    public function getModelForRoute(ProviderRoute $route, string $name): ?ProviderModel
    {
        return $route->models->first(function (ProviderModel $model) use ($name): bool {
            return Str::lower($model->name) === Str::lower($name)
                || Str::lower((string) $model->external_name) === Str::lower($name);
        });
    }

    public function getDefaultModelForRoute(ProviderRoute $route): ?ProviderModel
    {
        return $route->models
            ->where('status', 'active')
            ->sortByDesc('is_default')
            ->sortBy('priority')
            ->first();
    }

    public function getProvidersByCapability(string $capability, string $harness = 'laravel_ai'): Collection
    {
        return $this->getRoutesByCapability($capability, $harness)
            ->map(fn (ProviderRoute $route): Provider => $route->provider)
            ->unique('id')
            ->values();
    }

    public function getRoutesByCapability(string $capability, string $harness = 'laravel_ai'): Collection
    {
        return $this->getRoutes($harness)
            ->filter(function (ProviderRoute $route) use ($capability): bool {
                return in_array($capability, $route->capability_tags ?? [], true);
            })
            ->values();
    }

    public function getBestProviderForCapability(string $capability, array $exclude = [], string $harness = 'laravel_ai'): ?Provider
    {
        return $this->getBestRouteForCapability($capability, $exclude, $harness)?->provider;
    }

    public function getBestRouteForCapability(string $capability, array $exclude = [], string $harness = 'laravel_ai'): ?ProviderRoute
    {
        $excludedNames = array_map(static fn (string $name): string => Str::lower($name), $exclude);

        return $this->getRoutesByCapability($capability, $harness)
            ->reject(fn (ProviderRoute $route): bool => in_array(Str::lower($route->provider->name), $excludedNames, true))
            ->filter(fn (ProviderRoute $route): bool => $this->canUseRoute($route))
            ->sortBy([
                fn (ProviderRoute $route): int => $this->statusRank($route->status),
                fn (ProviderRoute $route): int => $this->capabilityPreferenceRank($route->provider, $capability),
                fn (ProviderRoute $route): int => $route->priority,
                fn (ProviderRoute $route): int => -1 * $this->requestsRemaining($route),
            ])
            ->first();
    }

    public function getFallbackProviderName(string $primaryProviderName, string $capability = 'chat', string $harness = 'laravel_ai'): ?string
    {
        return $this->getBestRouteForCapability($capability, [$primaryProviderName], $harness)?->provider?->name;
    }

    public function getActiveProviders(string $harness = 'laravel_ai'): Collection
    {
        return $this->getRoutes($harness)
            ->filter(fn (ProviderRoute $route): bool => $this->canUseRoute($route))
            ->map(fn (ProviderRoute $route): Provider => $route->provider)
            ->unique('id')
            ->values();
    }

    public function addProvider(array $data): Provider
    {
        $provider = Provider::query()->create($data);

        $this->refresh();

        return $provider;
    }

    public function removeProvider(int $providerId): bool
    {
        $provider = $this->getProvider($providerId);

        if (! $provider instanceof Provider) {
            return false;
        }

        $deleted = (bool) $provider->delete();

        $this->refresh();

        return $deleted;
    }

    public function canUseProvider(string $name, string $harness = 'laravel_ai'): bool
    {
        $route = $this->getProviderRouteForHarness($name, $harness);

        return $route instanceof ProviderRoute ? $this->canUseRoute($route) : false;
    }

    public function canUseRoute(ProviderRoute|string $route): bool
    {
        $route = is_string($route) ? $this->getRouteByName($route) : $route;

        if (! $route instanceof ProviderRoute) {
            return false;
        }

        return in_array($route->status, ['active', 'degraded'], true) && ! $this->isRouteRateLimited($route);
    }

    public function isProviderRateLimited(string $name, string $harness = 'laravel_ai'): bool
    {
        $route = $this->getProviderRouteForHarness($name, $harness);

        return $route instanceof ProviderRoute ? $this->isRouteRateLimited($route) : true;
    }

    public function isRouteRateLimited(ProviderRoute|string $route): bool
    {
        $route = is_string($route) ? $this->getRouteByName($route) : $route;

        if (! $route instanceof ProviderRoute) {
            return true;
        }

        if (in_array($route->status, ['rate-limited', 'disabled'], true)) {
            return true;
        }

        $usageSnapshot = $route->usage_snapshot ?? [];
        $requestsRemaining = $usageSnapshot['requests_remaining'] ?? null;
        $resetAt = $this->parseDate($usageSnapshot['reset_at'] ?? null);

        if (is_numeric($requestsRemaining) && (int) $requestsRemaining <= 0) {
            return $resetAt instanceof CarbonInterface ? $resetAt->isFuture() : true;
        }

        return false;
    }

    public function recordUsage(string $providerName, ?string $model = null, string $harness = 'laravel_ai'): bool
    {
        $route = $this->resolveRouteForUsage($providerName, $model, $harness);

        return $route instanceof ProviderRoute ? $this->recordRouteUsage($route, $model) : false;
    }

    public function recordRouteUsage(ProviderRoute|string $route, ?string $model = null): bool
    {
        $route = is_string($route) ? $this->getRouteByName($route) : $route;

        if (! $route instanceof ProviderRoute) {
            return false;
        }

        $rateLimits = $route->rate_limits ?? [];
        $usageSnapshot = $route->usage_snapshot ?? [];
        $windowHours = (int) ($rateLimits['window_hours'] ?? 5);
        $requestsPerWindow = $rateLimits['requests_per_window'] ?? null;
        $used = (int) ($usageSnapshot['requests_used'] ?? 0) + 1;
        $remaining = is_numeric($requestsPerWindow) ? max(0, (int) $requestsPerWindow - $used) : ($usageSnapshot['requests_remaining'] ?? null);

        $usageSnapshot['last_used_at'] = now()->toISOString();
        $usageSnapshot['requests_used'] = $used;
        $usageSnapshot['requests_remaining'] = $remaining;
        $usageSnapshot['current_model'] = $model;
        $usageSnapshot['reset_at'] = $usageSnapshot['reset_at'] ?? now()->addHours(max($windowHours, 1))->toISOString();

        $route->usage_snapshot = $usageSnapshot;
        $saved = $route->save();

        $this->syncRouteStatus($route);

        return $saved;
    }

    public function markRateLimited(string $name, ?CarbonInterface $resetAt = null, string $harness = 'laravel_ai'): bool
    {
        $route = $this->getProviderRouteForHarness($name, $harness);

        return $route instanceof ProviderRoute ? $this->markRouteRateLimited($route, $resetAt) : false;
    }

    public function markRouteRateLimited(ProviderRoute|string $route, ?CarbonInterface $resetAt = null): bool
    {
        $route = is_string($route) ? $this->getRouteByName($route) : $route;

        if (! $route instanceof ProviderRoute) {
            return false;
        }

        $usageSnapshot = $route->usage_snapshot ?? [];
        $usageSnapshot['requests_remaining'] = 0;
        $usageSnapshot['reset_at'] = ($resetAt ?? now()->addHours(5))->toISOString();

        $route->usage_snapshot = $usageSnapshot;
        $route->status = 'rate-limited';

        return $route->save();
    }

    public function providerStatusSnapshot(?string $harness = null): array
    {
        return $this->syncStatuses($harness)
            ->map(function (ProviderRoute $route): array {
                return [
                    'provider' => $route->provider->name,
                    'route' => $route->name,
                    'harness' => $route->harness,
                    'auth_mode' => $route->auth_mode,
                    'status' => $route->status,
                    'requests_remaining' => $this->requestsRemaining($route),
                    'reset_at' => $route->usage_snapshot['reset_at'] ?? null,
                    'capabilities' => $route->capability_tags ?? [],
                ];
            })
            ->all();
    }

    public function syncStatuses(?string $harness = null): Collection
    {
        return $this->getRoutes($harness)
            ->map(fn (ProviderRoute $route): ProviderRoute => $this->syncRouteStatus($route))
            ->values();
    }

    protected function syncRouteStatus(ProviderRoute $route): ProviderRoute
    {
        $status = $route->status;

        if ($status !== 'disabled') {
            $status = $this->isRouteRateLimited($route) ? 'rate-limited' : 'active';
        }

        if ($route->status !== $status) {
            $route->status = $status;
            $route->save();
        }

        return $route->refresh();
    }

    protected function requestsRemaining(ProviderRoute $route): int
    {
        $remaining = $route->usage_snapshot['requests_remaining'] ?? null;

        return is_numeric($remaining) ? (int) $remaining : PHP_INT_MAX;
    }

    protected function refreshRoutes(): Collection
    {
        $this->refresh();

        return $this->routes;
    }

    protected function resolveRouteForUsage(string $providerName, ?string $model, string $harness): ?ProviderRoute
    {
        if ($model !== null && $model !== '') {
            $route = $this->getRoutes($harness)
                ->first(function (ProviderRoute $route) use ($model): bool {
                    return $route->models->contains(function (ProviderModel $providerModel) use ($model): bool {
                        return Str::lower($providerModel->name) === Str::lower($model)
                            || Str::lower((string) $providerModel->external_name) === Str::lower($model);
                    });
                });

            if ($route instanceof ProviderRoute && Str::lower($route->provider->name) === Str::lower($providerName)) {
                return $route;
            }
        }

        return $this->getProviderRouteForHarness($providerName, $harness);
    }

    protected function capabilityPreferenceRank(Provider $provider, string $capability): int
    {
        $preferences = $provider->priority_preferences ?? [];

        $preference = $preferences[$capability]
            ?? $preferences['default']
            ?? 100;

        return is_numeric($preference) ? (int) $preference : 100;
    }

    protected function statusRank(string $status): int
    {
        return match ($status) {
            'active' => 0,
            'degraded' => 1,
            'rate-limited' => 2,
            default => 3,
        };
    }

    protected function parseDate(mixed $value): ?CarbonInterface
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return Carbon::parse($value);
    }
}
