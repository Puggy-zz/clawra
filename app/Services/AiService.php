<?php

declare(strict_types=1);

namespace App\Services;

use App\Ai\Agents\PromptFallbackAgent;
use App\Models\Provider;
use App\Models\ProviderModel;
use App\Models\ProviderRoute;
use Illuminate\Support\Facades\Log;
use Throwable;

class AiService
{
    public function __construct(protected ProviderRegistry $providerRegistry) {}

    public function prompt(string $prompt, ?string $model = null): array
    {
        if (! $this->isAvailable()) {
            return [
                'success' => false,
                'error' => 'Laravel AI SDK is not available.',
                'timestamp' => now()->toISOString(),
            ];
        }

        ['provider' => $provider, 'model' => $resolvedModel, 'capability' => $capability, 'route' => $route] = $this->resolveProviderAndModel($model);

        if (! $this->providerRegistry->canUseRoute($route)) {
            return [
                'success' => false,
                'error' => sprintf('Provider [%s] is currently unavailable.', $provider),
                'provider' => $provider,
                'model' => $resolvedModel,
                'route' => $route?->name,
                'timestamp' => now()->toISOString(),
            ];
        }

        try {
            $response = PromptFallbackAgent::make()->prompt(
                $prompt,
                provider: $provider,
                model: $resolvedModel,
            );

            if ($route instanceof ProviderRoute) {
                $this->providerRegistry->recordRouteUsage($route, $resolvedModel);
            }

            return [
                'success' => true,
                'text' => trim((string) $response),
                'provider' => $provider,
                'model' => $resolvedModel,
                'route' => $route?->name,
                'capability' => $capability,
                'timestamp' => now()->toISOString(),
            ];
        } catch (Throwable $exception) {
            Log::error('AI prompt failed: '.$exception->getMessage(), [
                'provider' => $provider,
                'model' => $resolvedModel,
            ]);

            if ($this->looksRateLimited($exception->getMessage()) && $route instanceof ProviderRoute) {
                $this->providerRegistry->markRouteRateLimited($route);
            }

            return [
                'success' => false,
                'error' => $exception->getMessage(),
                'provider' => $provider,
                'model' => $resolvedModel,
                'route' => $route?->name,
                'capability' => $capability,
                'timestamp' => now()->toISOString(),
            ];
        }
    }

    public function promptWithFallback(string $prompt, string $primaryModel, string $fallbackModel): array
    {
        $primary = $this->resolveProviderAndModel($primaryModel);
        $fallback = $this->resolveProviderAndModel($fallbackModel);

        if (! $this->providerRegistry->canUseRoute($primary['route'])) {
            return $this->prompt($prompt, $fallbackModel);
        }

        $response = $this->prompt($prompt, $primaryModel);

        if ($response['success']) {
            return $response;
        }

        Log::warning('Primary AI model failed, using fallback.', [
            'primary_provider' => $primary['provider'],
            'fallback_provider' => $fallback['provider'],
            'error' => $response['error'] ?? 'Unknown error',
        ]);

        if (! $this->providerRegistry->canUseRoute($fallback['route'])) {
            return $response;
        }

        return $this->prompt($prompt, $fallbackModel);
    }

    public function resolveProviderAndModel(?string $model): array
    {
        if ($model === null || $model === '') {
            $route = $this->providerRegistry->getBestRouteForCapability('chat', harness: 'laravel_ai');
            $providerModel = $route instanceof ProviderRoute ? $this->providerRegistry->getDefaultModelForRoute($route) : null;

            return [
                'provider' => $route?->provider?->name ?? 'synthetic',
                'model' => $providerModel?->external_name ?? $providerModel?->name,
                'capability' => 'chat',
                'route' => $route,
            ];
        }

        if (in_array($model, ['synthetic', 'deepseek-v3', 'kimi-k2-instruct', 'glm-4.7'], true)) {
            $route = $this->providerRegistry->getProviderRouteForHarness('synthetic', 'laravel_ai');

            return [
                'provider' => $route?->provider?->name ?? 'synthetic',
                'model' => $model === 'synthetic' ? null : $model,
                'capability' => 'chat',
                'route' => $route,
            ];
        }

        if (in_array($model, ['gemini', 'gemini-2.5-pro', 'claude-3-haiku', 'gpt-4'], true)) {
            $route = $this->providerRegistry->getProviderRouteForHarness('gemini', 'laravel_ai');

            return [
                'provider' => $route?->provider?->name ?? 'gemini',
                'model' => $model === 'gemini' ? null : 'gemini-2.5-pro',
                'capability' => 'chat',
                'route' => $route,
            ];
        }

        $route = $this->providerRegistry->getRouteByName($model);

        if ($route instanceof ProviderRoute) {
            $providerModel = $this->providerRegistry->getDefaultModelForRoute($route);

            return [
                'provider' => $route->provider->name,
                'model' => $providerModel?->external_name ?? $providerModel?->name,
                'capability' => 'chat',
                'route' => $route,
            ];
        }

        $providerRoute = $this->providerRegistry->getProviderRouteForHarness($model, 'laravel_ai');

        if ($providerRoute instanceof ProviderRoute) {
            $providerModel = $this->providerRegistry->getDefaultModelForRoute($providerRoute);

            return [
                'provider' => $providerRoute->provider->name,
                'model' => $providerModel?->external_name ?? $providerModel?->name,
                'capability' => 'chat',
                'route' => $providerRoute,
            ];
        }

        $matchedModel = $this->findLaravelAiProviderModel($model);

        if ($matchedModel instanceof ProviderModel) {
            $route = $matchedModel->route;

            return [
                'provider' => $route->provider->name,
                'model' => $matchedModel->external_name ?? $matchedModel->name,
                'capability' => $matchedModel->capabilities[0] ?? 'chat',
                'route' => $route,
            ];
        }

        return match ($model) {
            'claude-3-5-sonnet' => $this->resolveProviderAndModel('glm-4.7'),
            default => [
                'provider' => 'synthetic',
                'model' => $model,
                'capability' => 'chat',
                'route' => $this->providerRegistry->getProviderRouteForHarness('synthetic', 'laravel_ai'),
            ],
        };
    }

    public function getBestProviderForCapability(string $capability): ?Provider
    {
        return $this->providerRegistry->getBestProviderForCapability($capability);
    }

    protected function findLaravelAiProviderModel(string $model): ?ProviderModel
    {
        return $this->providerRegistry->getRoutes('laravel_ai')
            ->flatMap(fn (ProviderRoute $route) => $route->models)
            ->first(function (ProviderModel $providerModel) use ($model): bool {
                return strcasecmp($providerModel->name, $model) === 0
                    || strcasecmp((string) $providerModel->external_name, $model) === 0;
            });
    }

    public function isAvailable(): bool
    {
        return class_exists(PromptFallbackAgent::class);
    }

    public function getAvailableModels(): array
    {
        return $this->providerRegistry->getRoutes('laravel_ai')->map(function (ProviderRoute $route): array {
            return [
                'id' => $route->id,
                'name' => $route->provider->name,
                'route' => $route->name,
                'status' => $route->status,
                'capabilities' => $route->capability_tags,
                'requests_remaining' => $route->usage_snapshot['requests_remaining'] ?? null,
                'reset_at' => $route->usage_snapshot['reset_at'] ?? null,
                'models' => $route->models->pluck('name')->values()->all(),
            ];
        })->values()->all();
    }

    protected function looksRateLimited(string $message): bool
    {
        $normalized = strtolower($message);

        return str_contains($normalized, 'rate')
            || str_contains($normalized, 'quota')
            || str_contains($normalized, '429');
    }
}
