<?php

namespace Admin\Permission\Http\Controllers;

use Illuminate\Http\Request;
use Admin\Permission\Models\AdminLoginLog;

class AdminLoginLogController extends AdminController
{
    public function index(Request $request)
    {
        $query = AdminLoginLog::with('user');

        if ($request->has('username') && $request->username) {
            $query->where('username', 'like', "%{$request->username}%");
        }

        if ($request->has('status') && $request->status !== null && $request->status !== '') {
            $query->where('status', $request->status);
        }

        if ($request->has('start_date') && $request->start_date) {
            $query->where('login_at', '>=', $request->start_date);
        }

        if ($request->has('end_date') && $request->end_date) {
            $query->where('login_at', '<=', $request->end_date . ' 23:59:59');
        }

        $logs = $query->orderBy('login_at', 'desc')->paginate($request->get('per_page', 15));

        return $this->paginate($logs);
    }

    public function show($hashId)
    {
        $log = AdminLoginLog::findOrFail($hashId);
        $log->load('user');
        return $this->success($log->toArray());
    }

    public function destroy($hashId)
    {
        $log = AdminLoginLog::findOrFail($hashId);
        $log->delete();
        return $this->success([], '日志删除成功');
    }

    public function batchDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array'
        ]);

        AdminLoginLog::whereIn('hash_id', $request->ids)->delete();

        return $this->success([], '日志批量删除成功');
    }

    public function statistics(Request $request)
    {
        $request->validate([
            'days' => 'required|integer|min:1|max:30'
        ]);

        $startDate = now()->subDays($request->days);

        $stats = AdminLoginLog::selectRaw(
            '
            DATE(login_at) as date,
            COUNT(*) as total_logins,
            SUM(CASE WHEN status = 1 THEN 1 ELSE 0 END) as success_logins,
            SUM(CASE WHEN status = 0 THEN 1 ELSE 0 END) as failed_logins
        '
        )
            ->where('login_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $this->success($stats->toArray());
    }
}
