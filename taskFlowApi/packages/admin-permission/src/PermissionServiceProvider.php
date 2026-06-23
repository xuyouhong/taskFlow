<?php

namespace Admin\Permission;

use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;
use Admin\Permission\Middleware\PermissionMiddleware;
use Admin\Permission\Middleware\OperationLogMiddleware;

class PermissionServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // 发布配置文件
        $this->publishes([
            __DIR__ . '/../config/permission.php' => config_path('permission.php'),
        ], 'admin-permission-config');

        // 发布数据库迁移
        $this->publishes([
            __DIR__ . '/../database/migrations' => database_path('migrations'),
        ], 'admin-permission-migrations');

        // 发布数据填充
        $this->publishes([
            __DIR__ . '/../database/Seeders/AdminPermissionSeeder.php' => database_path('Seeders/AdminPermissionSeeder.php'),
        ], 'admin-permission-seeders');

        // 加载路由
        $this->loadRoutesFrom(__DIR__ . '/../routes/api.php');

        // 注册中间件
        $router = $this->app->make(Router::class);
        $router->aliasMiddleware('permission', PermissionMiddleware::class);
        $router->aliasMiddleware('operation.log', OperationLogMiddleware::class);
    }

    public function register()
    {
        // 合并配置
        $this->mergeConfigFrom(
            __DIR__ . '/../config/permission.php', 'permission'
        );
    }
}