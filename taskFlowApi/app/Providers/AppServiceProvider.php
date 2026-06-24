<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadRoutesFrom(__DIR__ . '/../../routes/scheduler.php');

        // 设置MySQL会话时区为Asia/Shanghai (UTC+8)
        try {
            DB::statement("SET time_zone = '+08:00'");
        } catch (\Exception $e) {
            // 忽略连接错误
        }
    }
}
