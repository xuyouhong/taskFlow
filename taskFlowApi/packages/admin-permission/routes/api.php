<?php

use Illuminate\Support\Facades\Route;
use Admin\Permission\Http\Controllers\AdminAuthController;
use Admin\Permission\Http\Controllers\AdminUserController;
use Admin\Permission\Http\Controllers\AdminRoleController;
use Admin\Permission\Http\Controllers\AdminPermissionController;
use Admin\Permission\Http\Controllers\AdminMenuController;
use Admin\Permission\Http\Controllers\AdminLoginLogController;
use Admin\Permission\Http\Controllers\AdminOperationLogController;
use Admin\Permission\Http\Controllers\AdminDashboardController;
use Admin\Permission\Http\Controllers\AdminUploadController;
use Admin\Permission\Http\Controllers\AdminNotificationController;
use Admin\Permission\Http\Controllers\AdminUserNotificationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->group(function () {

    // 公开路由 - 不需要认证
    Route::get('captcha', [AdminAuthController::class, 'captcha'])->name('captcha');
    Route::post('login', [AdminAuthController::class, 'login'])->name('login');

    // 需要验证登录认证和记录操作日志的路由
    Route::middleware(['auth:sanctum', 'operation.log'])->group(function () {
        // 用户信息
        Route::get('user', [AdminAuthController::class, 'user']);
        Route::get('user-menus', [AdminMenuController::class, 'userMenus']);
        Route::post('logout', [AdminAuthController::class, 'logout']);
        Route::post('refresh', [AdminAuthController::class, 'refresh']);

        // 个人中心
        Route::put('profile', [AdminUserController::class, 'updateProfile']);
        Route::put('password', [AdminUserController::class, 'changePassword']);

        // 公共路由 - 不需要权限验证和操作日志
        Route::get('menus-tree', [AdminMenuController::class, 'tree']);
        Route::get('roles/permissions/list', [AdminRoleController::class, 'permissions']);
        Route::get('roles/menus/list', [AdminRoleController::class, 'menus']);
        Route::get('users/roles/list', [AdminUserController::class, 'roles']);
        Route::get('roles/{roleId}/menus', [AdminRoleController::class, 'roleMenus']);
        Route::get('roles/{roleId}/permissions', [AdminRoleController::class, 'rolePermissions']);

        // 文件上传
        Route::post('upload', [AdminUploadController::class, 'upload']);
        Route::post('upload/batch', [AdminUploadController::class, 'batchUpload']);

        // 仪表盘
        Route::get('dashboard', [AdminDashboardController::class, 'index']);
        Route::get('dashboard/chart', [AdminDashboardController::class, 'chartData']);
        Route::get('dashboard/quick-stats', [AdminDashboardController::class, 'quickStats']);
        Route::get('dashboard/recent-logins', [AdminDashboardController::class, 'recentLogins']);

        // 用户通知相关路由
        Route::prefix('user-notifications')->group(function () {
            // 获取用户通知列表
            Route::get('', [AdminUserNotificationController::class, 'index'])->name('user-notifications.index');
            // 获取未读通知数量
            Route::get('unread-count', [AdminUserNotificationController::class, 'unreadCount'])->name('user-notifications.unread-count');
            // 标记单个通知为已读
            Route::post('{id}/read', [AdminUserNotificationController::class, 'markAsRead'])->name('user-notifications.read');
            // 标记所有通知为已读
            Route::post('read-all', [AdminUserNotificationController::class, 'markAllAsRead'])->name('user-notifications.read-all');
            // 获取单个通知详情
            Route::get('{id}', [AdminUserNotificationController::class, 'show'])->name('user-notifications.show');
            // 删除通知
            Route::delete('{id}', [AdminUserNotificationController::class, 'destroy'])->name('user-notifications.destroy');
            // 批量删除通知
            Route::post('batch-destroy', [AdminUserNotificationController::class, 'batchDestroy'])->name('user-notifications.batch-destroy');
        });
    });

    // 需要认证、权限验证和操作日志的路由
    Route::middleware(['auth:sanctum', 'permission', 'operation.log'])->group(function () {
        // 用户管理
        Route::apiResource('users', AdminUserController::class);
        Route::post('users/batch-status', [AdminUserController::class, 'batchUpdateStatus'])->name('users.batch-status');

        // 角色管理
        Route::apiResource('roles', AdminRoleController::class);
        Route::post('roles/batch-status', [AdminRoleController::class, 'batchUpdateStatus'])->name('roles.batch-status');
        Route::post('roles/assign-menus/{roleId}', [AdminRoleController::class, 'assignMenusToRole'])->name('roles.assign-menus');
        Route::post('roles/assign-permissions/{roleId}', [AdminRoleController::class, 'assignPermissionsToRole'])->name('roles.assign-permissions');

        // 权限管理
        Route::apiResource('permissions', AdminPermissionController::class);
        Route::post('permissions/batch-status', [AdminPermissionController::class, 'batchUpdateStatus'])->name('permissions.batch-status');
        Route::post('permissions/sync-routes', [AdminPermissionController::class, 'syncRoutes'])->name('permissions.sync-routes');

        // 菜单管理
        Route::apiResource('menus', AdminMenuController::class);
        Route::put('menus/sort', [AdminMenuController::class, 'updateSort'])->name('menus.sort');
        Route::post('menus/batch-status', [AdminMenuController::class, 'batchUpdateStatus'])->name('menus.batch-status');

        // 登录日志
        Route::post('login-logs/batch-destroy', [AdminLoginLogController::class, 'batchDestroy'])->name('login-logs.batch-destroy');
        Route::get('login-logs/statistics', [AdminLoginLogController::class, 'statistics'])->name('login-logs.statistics');
        Route::apiResource('login-logs', AdminLoginLogController::class)->only(['index', 'show', 'destroy']);

        // 操作日志
        Route::post('operation-logs/batch-destroy', [AdminOperationLogController::class, 'batchDestroy'])->name('operation-logs.batch-destroy');
        Route::post('operation-logs/clean', [AdminOperationLogController::class, 'clean'])->name('operation-logs.clean');
        Route::get('operation-logs/statistics', [AdminOperationLogController::class, 'statistics'])->name('operation-logs.statistics');
        Route::apiResource('operation-logs', AdminOperationLogController::class)->only(['index', 'show', 'destroy']);

        // 通知通告管理
        Route::apiResource('notifications', AdminNotificationController::class);
        Route::post('notifications/{id}/publish', [AdminNotificationController::class, 'publish'])->name('notifications.publish');
        Route::post('notifications/{id}/revoke', [AdminNotificationController::class, 'revoke'])->name('notifications.revoke');

    });
});