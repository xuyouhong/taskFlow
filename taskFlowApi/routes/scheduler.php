<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\ProjectController;
use App\Http\Controllers\Api\V1\TaskController;
use App\Http\Controllers\Api\V1\TaskLogController;
use App\Http\Controllers\Api\V1\NodeController;
use App\Http\Controllers\Api\V1\AgentController;
use App\Http\Controllers\Api\V1\NotificationChannelController;

/*
|--------------------------------------------------------------------------
| API Routes - 定时任务调度管理系统
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {
    // Agent回调接口（无需认证，使用Token验证）
    Route::post('agents/register', [AgentController::class, 'register'])->name('agents.register');
    Route::post('agents/heartbeat', [AgentController::class, 'heartbeat'])->name('agents.heartbeat');
    Route::post('agents/callback', [AgentController::class, 'callback'])->name('agents.callback');

    // 需要认证、权限验证和操作日志的路由
    Route::middleware(['auth:sanctum', 'permission', 'operation.log'])->group(function () {
        // 项目管理
        Route::get('projects', [ProjectController::class, 'index'])->name('projects.index');
        Route::post('projects', [ProjectController::class, 'store'])->name('projects.store');
        Route::get('projects/{hashId}', [ProjectController::class, 'show'])->name('projects.show');
        Route::put('projects/{hashId}', [ProjectController::class, 'update'])->name('projects.update');
        Route::delete('projects/{hashId}', [ProjectController::class, 'destroy'])->name('projects.destroy');
        Route::get('projects/{hashId}/members', [ProjectController::class, 'members'])->name('projects.members');
        Route::post('projects/{hashId}/members', [ProjectController::class, 'addMember'])->name('projects.add-member');
        Route::delete('projects/{hashId}/members/{userId}', [ProjectController::class, 'removeMember'])->name('projects.remove-member');

        // 任务管理
        Route::get('tasks', [TaskController::class, 'index'])->name('tasks.index');
        Route::post('tasks', [TaskController::class, 'store'])->name('tasks.store');
        Route::get('tasks/{hashId}', [TaskController::class, 'show'])->name('tasks.show');
        Route::put('tasks/{hashId}', [TaskController::class, 'update'])->name('tasks.update');
        Route::delete('tasks/{hashId}', [TaskController::class, 'destroy'])->name('tasks.destroy');
        Route::post('tasks/{hashId}/trigger', [TaskController::class, 'trigger'])->name('tasks.trigger');
        Route::post('tasks/{hashId}/pause', [TaskController::class, 'pause'])->name('tasks.pause');
        Route::post('tasks/{hashId}/resume', [TaskController::class, 'resume'])->name('tasks.resume');
        Route::get('tasks/{hashId}/logs', [TaskController::class, 'logs'])->name('tasks.logs');

        // 执行日志
        Route::get('logs', [TaskLogController::class, 'index'])->name('logs.index');
        Route::get('logs/{hashId}', [TaskLogController::class, 'show'])->name('logs.show');
        Route::get('logs/archive', [TaskLogController::class, 'archive'])->name('logs.archive');

        // 节点管理
        Route::get('nodes', [NodeController::class, 'index'])->name('nodes.index');
        Route::post('nodes', [NodeController::class, 'store'])->name('nodes.store');
        Route::get('nodes/{hashId}', [NodeController::class, 'show'])->name('nodes.show');
        Route::put('nodes/{hashId}', [NodeController::class, 'update'])->name('nodes.update');
        Route::delete('nodes/{hashId}', [NodeController::class, 'destroy'])->name('nodes.destroy');

        // 通知渠道管理
        Route::get('notification-channels', [NotificationChannelController::class, 'index'])->name('notification-channels.index');
        Route::post('notification-channels', [NotificationChannelController::class, 'store'])->name('notification-channels.store');
        Route::get('notification-channels/{hashId}', [NotificationChannelController::class, 'show'])->name('notification-channels.show');
        Route::put('notification-channels/{hashId}', [NotificationChannelController::class, 'update'])->name('notification-channels.update');
        Route::delete('notification-channels/{hashId}', [NotificationChannelController::class, 'destroy'])->name('notification-channels.destroy');
    });
});
