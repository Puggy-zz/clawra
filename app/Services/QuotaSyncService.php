<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ProviderRoute;
use Illuminate\Support\Facades\Http;

class QuotaSyncService
{
    public function syncAll(): array
    {
        $results = [];

        $routes = ProviderRoute::query()->get()->filter(function (ProviderRoute $route): bool {
            $rateLimits = $route->rate_limits ?? [];

            return (bool) ($rateLimits['has_quotas_api'] ?? false);
        });

        foreach ($routes as $route) {
            $results[] = $this->syncRoute($route);
        }

        return $results;
    }

    public function syncRoute(ProviderRoute $route): array
    {
        $quotasUrl = config('services.synthetic.quotas_url');
        $apiKey = config('services.synthetic.api_key');

        if (! $quotasUrl || ! $apiKey) {
            return [
                'route' => $route->name,
                'success' => false,
                'reason' => 'Missing quotas URL or API key configuration.',
            ];
        }

        $response = Http::withToken($apiKey)
            ->timeout(10)
            ->get($quotasUrl);

        if (! $response->successful()) {
            return [
                'route' => $route->name,
                'success' => false,
                'reason' => 'HTTP '.$response->status(),
            ];
        }

        $data = $response->json('subscription', []);
        $limit = $data['limit'] ?? null;
        $used = $data['requests'] ?? null;
        $renewsAt = $data['renewsAt'] ?? null;

        $usageSnapshot = $route->usage_snapshot ?? [];

        if (is_numeric($limit) && is_numeric($used)) {
            $usageSnapshot['requests_remaining'] = max(0, (int) $limit - (int) $used);
            $usageSnapshot['weekly_requests_remaining'] = max(0, (int) $limit - (int) $used);
            $usageSnapshot['weekly_requests_used'] = (int) $used;
        }

        if ($renewsAt !== null) {
            $usageSnapshot['weekly_reset_at'] = $renewsAt;
        }

        $route->usage_snapshot = $usageSnapshot;
        $route->save();

        app(ProviderRegistry::class)->syncStatuses();

        return [
            'route' => $route->name,
            'success' => true,
            'requests_remaining' => $usageSnapshot['requests_remaining'] ?? null,
            'weekly_reset_at' => $usageSnapshot['weekly_reset_at'] ?? null,
        ];
    }
}
