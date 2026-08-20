<?php

use App\Http\Controllers\MeController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/me', MeController::class)->name('me');

        Route::middleware('verified')->group(function (): void {
            Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
            Route::get('/tasks', [TaskController::class, 'index'])->name('tasks.index');
            Route::get('/tasks/{task}', [TaskController::class, 'show'])->name('tasks.show');
        });
    });
});
