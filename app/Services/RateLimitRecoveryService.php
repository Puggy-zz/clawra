<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ProviderRoute;
use Illuminate\Support\Carbon;

class RateLimitRecoveryService
{
    public function recoverExpiredWindows(): array
    {
        $recovered = [];

        $routes = ProviderRoute::query()
            ->where('status', 'rate-limited')
            ->get();

        foreach ($routes as $route) {
            $usageSnapshot = $route->usage_snapshot ?? [];
            $resetAt = isset($usageSnapshot['reset_at']) ? Carbon::parse($usageSnapshot['reset_at']) : null;

            if ($resetAt instanceof \Carbon\Carbon && $resetAt->isPast()) {
                $this->clearRouteRateLimit($route);
                $recovered[] = [
                    'route' => $route->name,
                    'provider' => $route->provider->name,
                    'reset_at' => $resetAt->toISOString(),
                ];
            }
        }

        return $recovered;
    }

    public function clearRouteRateLimit(ProviderRoute $route): void
    {
        $usageSnapshot = $route->usage_snapshot ?? [];
        $rateLimits = $route->rate_limits ?? [];
        $requestsPerWindow = $rateLimits['requests_per_window'] ?? null;

        $usageSnapshot['requests_remaining'] = is_numeric($requestsPerWindow) ? (int) $requestsPerWindow : null;
        $usageSnapshot['requests_used'] = 0;
        unset($usageSnapshot['reset_at']);

        $route->usage_snapshot = $usageSnapshot;
        $route->status = 'active';
        $route->save();
    }

    public function clearWeeklyCounters(ProviderRoute $route): void
    {
        $usageSnapshot = $route->usage_snapshot ?? [];
        $rateLimits = $route->rate_limits ?? [];
        $weeklyLimit = $rateLimits['weekly_requests_limit'] ?? null;

        $usageSnapshot['weekly_requests_used'] = 0;
        $usageSnapshot['weekly_requests_remaining'] = is_numeric($weeklyLimit) ? (int) $weeklyLimit : null;
        $usageSnapshot['weekly_reset_at'] = Carbon::now('UTC')->startOfWeek(Carbon::MONDAY)->addWeek()->toISOString();

        $route->usage_snapshot = $usageSnapshot;
        $route->save();
    }
}
