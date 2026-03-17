<?php

declare(strict_types=1);

use App\Http\Controllers\AgentController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProviderController;
use App\Http\Controllers\SubtaskController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\WorkflowController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::apiResource('projects', ProjectController::class);
Route::apiResource('tasks', TaskController::class);
Route::apiResource('agents', AgentController::class);
Route::apiResource('providers', ProviderController::class);
Route::apiResource('workflows', WorkflowController::class);
Route::apiResource('subtasks', SubtaskController::class);

// Task-specific routes
Route::post('tasks/create-with-workflow', [TaskController::class, 'createWithWorkflow']);

// Workflow-specific routes
Route::get('workflows/{id}/steps', [WorkflowController::class, 'getSteps']);

// Agent-specific routes
Route::get('agents/tool/{tool}', [AgentController::class, 'getByTool']);

// Provider-specific routes
Route::get('providers/active', [ProviderController::class, 'getActive']);
Route::get('providers/capability/{capability}', [ProviderController::class, 'getByCapability']);

// Subtask-specific routes
Route::post('subtasks/task/{taskId}', [SubtaskController::class, 'createForTask']);

// Log routes
Route::post('logs/task', [LogController::class, 'logTaskEvent']);
Route::post('logs/review', [LogController::class, 'logReviewEvent']);
Route::post('logs/heartbeat', [LogController::class, 'logHeartbeatEvent']);
Route::get('logs/task/{taskId}', [LogController::class, 'getTaskLogs']);
Route::get('logs/review/{taskId}', [LogController::class, 'getReviewLogs']);
