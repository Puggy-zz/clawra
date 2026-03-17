<?php

declare(strict_types=1);

use App\Agents\SimpleChatAgent;
use App\Http\Controllers\CoordinatorController;
use App\Services\SimpleChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/coordinator', [CoordinatorController::class, 'index']);
Route::post('/coordinator/message', [CoordinatorController::class, 'processMessage']);
Route::post('/coordinator/conversations', [CoordinatorController::class, 'storeConversation']);
Route::post('/coordinator/projects', [CoordinatorController::class, 'storeProject']);
Route::post('/coordinator/workflows', [CoordinatorController::class, 'storeWorkflow']);
Route::post('/coordinator/tasks', [CoordinatorController::class, 'storeTask']);
Route::patch('/coordinator/tasks/{task}', [CoordinatorController::class, 'updateTask']);
Route::delete('/coordinator/tasks/{task}', [CoordinatorController::class, 'destroyTask']);
Route::post('/coordinator/agents', [CoordinatorController::class, 'storeAgent']);
Route::patch('/coordinator/agents/{agent}', [CoordinatorController::class, 'updateAgent']);
Route::delete('/coordinator/agents/{agent}', [CoordinatorController::class, 'destroyAgent']);
Route::post('/coordinator/agent-runtimes', [CoordinatorController::class, 'storeAgentRuntime']);
Route::patch('/coordinator/agent-runtimes/{agentRuntime}', [CoordinatorController::class, 'updateAgentRuntime']);
Route::delete('/coordinator/agent-runtimes/{agentRuntime}', [CoordinatorController::class, 'destroyAgentRuntime']);
Route::post('/coordinator/providers', [CoordinatorController::class, 'storeProvider']);
Route::patch('/coordinator/providers/{provider}', [CoordinatorController::class, 'updateProvider']);
Route::delete('/coordinator/providers/{provider}', [CoordinatorController::class, 'destroyProvider']);
Route::post('/coordinator/provider-routes', [CoordinatorController::class, 'storeProviderRoute']);
Route::patch('/coordinator/provider-routes/{providerRoute}', [CoordinatorController::class, 'updateProviderRoute']);
Route::delete('/coordinator/provider-routes/{providerRoute}', [CoordinatorController::class, 'destroyProviderRoute']);
Route::post('/coordinator/provider-models', [CoordinatorController::class, 'storeProviderModel']);
Route::patch('/coordinator/provider-models/{providerModel}', [CoordinatorController::class, 'updateProviderModel']);
Route::delete('/coordinator/provider-models/{providerModel}', [CoordinatorController::class, 'destroyProviderModel']);
Route::post('/coordinator/heartbeat', [CoordinatorController::class, 'runHeartbeat']);

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
