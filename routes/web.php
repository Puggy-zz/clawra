<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

Route::get('/', function (): \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View {
    return view('welcome');
});

Route::get('/test', function () {
    return response()->json(['message' => 'Clawra is working!']);
});
