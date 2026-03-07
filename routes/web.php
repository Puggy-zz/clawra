<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CoordinatorController;

Route::get('/', [CoordinatorController::class, 'index']);

Route::post('/coordinator/message', [CoordinatorController::class, 'processMessage']);

Route::get('/test', function () {
    return response()->json(['message' => 'Clawra is working!']);
});
