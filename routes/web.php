<?php

declare(strict_types=1);

use App\Agents\SimpleChatAgent;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\HomeController;
use App\Services\SimpleChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index']);

Route::prefix('admin')->group(function () {
    Route::post('heartbeat', [AdminController::class, 'runHeartbeat']);

    Route::post('projects', [AdminController::class, 'storeProject']);
    Route::patch('projects/{project}', [AdminController::class, 'updateProject']);
    Route::delete('projects/{project}', [AdminController::class, 'destroyProject']);

    Route::post('tasks', [AdminController::class, 'storeTask']);
    Route::patch('tasks/{task}', [AdminController::class, 'updateTask']);
    Route::delete('tasks/{task}', [AdminController::class, 'destroyTask']);

    Route::post('providers', [AdminController::class, 'storeProvider']);
    Route::patch('providers/{provider}', [AdminController::class, 'updateProvider']);
    Route::delete('providers/{provider}', [AdminController::class, 'destroyProvider']);

    Route::post('provider-routes', [AdminController::class, 'storeProviderRoute']);
    Route::patch('provider-routes/{providerRoute}', [AdminController::class, 'updateProviderRoute']);
    Route::delete('provider-routes/{providerRoute}', [AdminController::class, 'destroyProviderRoute']);

    Route::post('provider-models', [AdminController::class, 'storeProviderModel']);
    Route::patch('provider-models/{providerModel}', [AdminController::class, 'updateProviderModel']);
    Route::delete('provider-models/{providerModel}', [AdminController::class, 'destroyProviderModel']);

    Route::post('agents', [AdminController::class, 'storeAgent']);
    Route::patch('agents/{agent}', [AdminController::class, 'updateAgent']);
    Route::delete('agents/{agent}', [AdminController::class, 'destroyAgent']);

    Route::post('agent-runtimes', [AdminController::class, 'storeAgentRuntime']);
    Route::patch('agent-runtimes/{agentRuntime}', [AdminController::class, 'updateAgentRuntime']);
    Route::delete('agent-runtimes/{agentRuntime}', [AdminController::class, 'destroyAgentRuntime']);

    Route::post('workflows', [AdminController::class, 'storeWorkflow']);
});

Route::get('/test', function () {
    return response()->json(['message' => 'Clawra is working!']);
});

Route::get('/test-agent', function () {
    try {
        $response = app(SimpleChatAgent::class)->prompt('Hello, how are you?');

        return response()->json([
            'status' => 'success',
            'response' => (string) $response,
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
        ], 500);
    }
});

Route::get('/test-search-agent', function (Request $request) {
    try {
        $response = app(SimpleChatService::class)->respondTo(
            $request->query('q', 'Search the web for the latest Laravel release and include source URLs in the answer.')
        );

        return response()->json([
            'status' => 'success',
            'response' => $response,
        ]);
    } catch (\Throwable $e) {
        return response()->json([
            'status' => 'error',
            'message' => $e->getMessage(),
        ], 500);
    }
});

Route::get('/test-config', function () {
    return response()->json([
        'synthetic_config' => config('ai.providers.synthetic'),
        'all_ai_config' => config('ai'),
    ]);
});
