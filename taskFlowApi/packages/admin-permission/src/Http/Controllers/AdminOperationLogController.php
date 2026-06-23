<?php

namespace Admin\Permission\Http\Controllers;

use Illuminate\Http\Request;
use Admin\Permission\Models\AdminOperationLog;

class AdminOperationLogController extends AdminController
{
    public function index(Request $request)
    {
        $query = AdminOperationLog::with('user');

        if ($request->has('username') && $request->username) {
            $query->where('username', 'like', "%{$request->username}%");
        }

        if ($request->has('method') && $request->method) {
            $query->where('method', $request->method);
        }

        if ($request->has('path') && $request->path) {
            $query->where('path', 'like', "%{$request->path}%");
        }

        if ($request->has('start_date') && $request->start_date) {
            $query->where('operated_at', '>=', $request->start_date);
        }

        if ($request->has('end_date') && $request->end_date) {
            $query->where('operated_at', '<=', $request->end_date . ' 23:59:59');
        }

        $logs = $query->orderBy('operated_at', 'desc')->paginate($request->get('per_page', 15));

        return $this->paginate($logs);
    }

    public function show($hashId)
    {
        $log = AdminOperationLog::findOrFail($hashId);
        $log->load('user');
        return $this->success($log->toArray());
    }

    public function destroy($hashId)
    {
        $log = AdminOperationLog::findOrFail($hashId);
        $log->delete();
        return $this->success([], '日志删除成功');
    }

    public function batchDestroy(Request $request)
    {
        $request->validate([
            'ids' => 'required|array'
        ]);

        AdminOperationLog::whereIn('hash_id', $request->ids)->delete();

        return $this->success([], '日志批量删除成功');
    }

    public function clean(Request $request)
    {
        $request->validate([
            'days' => 'required|integer|min:1'
        ]);

        $date    = now()->subDays($request->days);
        $deleted = AdminOperationLog::where('operated_at', '<', $date)->delete();

        return $this->success([], "成功清理 {$deleted} 条操作日志");
    }

    public function statistics(Request $request)
    {
        $request->validate([
            'days' => 'required|integer|min:1|max:30'
        ]);

        $startDate = now()->subDays($request->days);

        $stats = AdminOperationLog::selectRaw(
            '
            DATE(operated_at) as date,
            COUNT(*) as total_operations,
            COUNT(DISTINCT user_id) as active_users,
            AVG(duration) as avg_duration
        '
        )
            ->where('operated_at', '>=', $startDate)
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $methodStats = AdminOperationLog::selectRaw(
            '
            method,
            COUNT(*) as count
        '
        )
            ->where('operated_at', '>=', $startDate)
            ->groupBy('method')
            ->get();

        return $this->success([
            'daily_stats'  => $stats->toArray(),
            'method_stats' => $methodStats->toArray()
        ]);
    }
}
