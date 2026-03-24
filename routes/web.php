<?php

declare(strict_types=1);

use App\Agents\SimpleChatAgent;
use App\Http\Controllers\HomeController;
use App\Services\SimpleChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index']);

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
