<?php

namespace Admin\Permission\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Admin\Permission\Models\AdminUser;
use Admin\Permission\Models\AdminLoginLog;
use Admin\Permission\Models\AdminOperationLog;

class AdminDashboardController extends AdminController
{
    public function index()
    {
        // 用户统计
        $userStats = [
            'total'       => AdminUser::count(),
            'active'      => AdminUser::where('status', 1)->count(),
            'today_login' => AdminLoginLog::whereDate('login_at', today())->count(),
        ];

        // 登录统计
        $loginStats = AdminLoginLog::selectRaw(
            '
            COUNT(*) as total,
            SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as success,
            SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as failed
        '
        )->whereDate('login_at', today())->first();

        // 操作统计
        $operationStats = AdminOperationLog::selectRaw(
            '
            COUNT(*) as total,
            COUNT(DISTINCT user_id) as active_users
        '
        )->whereDate('operated_at', today())->first();

        // 最近登录
        $recentLogins = AdminLoginLog::with('user')
            ->where('status', 1)
            ->orderBy('login_at', 'desc')
            ->limit(10)
            ->get();

        // 系统信息
        $systemInfo = [
            'php_version'         => PHP_VERSION,
            'laravel_version'     => app()->version(),
            'server_software'     => $_SERVER['SERVER_SOFTWARE'] ?? '',
            'database_connection' => config('database.default'),
            'timezone'            => config('app.timezone'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'memory_limit'        => ini_get('memory_limit'),
        ];

        return $this->success([
            'user_stats'      => $userStats,
            'login_stats'     => $loginStats,
            'operation_stats' => $operationStats,
            'recent_logins'   => $recentLogins,
            'system_info'     => $systemInfo,
        ]);
    }

    public function chartData(Request $request)
    {
        $request->validate([
            'type' => 'required|in:login,operation',
            'days' => 'required|integer|min:1|max:90'
        ]);

        $startDate = now()->subDays($request->days);

        if ($request->type === 'login') {
            $data = AdminLoginLog::selectRaw(
                '
                DATE(login_at) as date,
                COUNT(*) as total,
                SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as success
            '
            )
                ->where('login_at', '>=', $startDate)
                ->groupBy('date')
                ->orderBy('date')
                ->get();
        } else {
            $data = AdminOperationLog::selectRaw(
                '
                DATE(operated_at) as date,
                COUNT(*) as total,
                COUNT(DISTINCT user_id) as active_users
            '
            )
                ->where('operated_at', '>=', $startDate)
                ->groupBy('date')
                ->orderBy('date')
                ->get();
        }

        return $this->success($data);
    }

    public function quickStats()
    {
        $today     = today();
        $yesterday = $today->copy()->subDay();

        // 今日数据
        $todayStats = [
            'logins'     => AdminLoginLog::whereDate('login_at', $today)->count(),
            'operations' => AdminOperationLog::whereDate('operated_at', $today)->count(),
            'new_users'  => AdminUser::whereDate('created_at', $today)->count(),
        ];

        // 昨日数据
        $yesterdayStats = [
            'logins'     => AdminLoginLog::whereDate('login_at', $yesterday)->count(),
            'operations' => AdminOperationLog::whereDate('operated_at', $yesterday)->count(),
            'new_users'  => AdminUser::whereDate('created_at', $yesterday)->count(),
        ];

        return $this->success([
            'today'     => $todayStats,
            'yesterday' => $yesterdayStats,
        ]);
    }

    /**
     * 获取用户最近登录记录
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function recentLogins(Request $request)
    {
        $limit = min((int)$request->get('limit', 10), 100);

        $recentLogins = AdminLoginLog::with('user')
            ->where('status', 1)
            ->orderBy('login_at', 'desc')
            ->limit($limit)
            ->get();

        return $this->success($recentLogins);
    }
}