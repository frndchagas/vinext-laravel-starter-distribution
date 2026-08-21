<?php

use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AuthCapabilitiesController;
use App\Http\Controllers\DeleteCurrentUserController;
use App\Http\Controllers\MeController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/auth/capabilities', AuthCapabilitiesController::class)->name('auth.capabilities');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/me', MeController::class)->name('me');
        Route::delete('/auth/user', DeleteCurrentUserController::class)->name('auth.user.destroy');

        Route::middleware('verified')->group(function (): void {
            Route::get('/admin/users', [AdminUserController::class, 'index'])->name('admin.users.index');
            Route::patch('/admin/users/{user}/role', [AdminUserController::class, 'updateRole'])->name('admin.users.role.update');

            Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');
            Route::get('/tasks', [TaskController::class, 'index'])->name('tasks.index');
            Route::get('/tasks/{task}', [TaskController::class, 'show'])->name('tasks.show');
        });
    });
});
