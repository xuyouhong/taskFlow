<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\TaskLog;
use App\Models\TaskLogDetail;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskLogController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = TaskLog::with(['task', 'node']);

        if ($request->filled('task_id')) {
            $query->where('task_id', $request->task_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('trigger_type')) {
            $query->where('trigger_type', $request->trigger_type);
        }

        if ($request->filled('start_date')) {
            $query->where('created_at', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->where('created_at', '<=', $request->end_date . ' 23:59:59');
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 15));

        return $this->success($logs);
    }

    public function show(string $hashId): JsonResponse
    {
        $log = TaskLog::with(['task', 'node', 'detail'])->find($hashId);
        if (!$log) {
            return $this->error('日志不存在', 1002);
        }
        return $this->success($log);
    }

    public function archive(Request $request): JsonResponse
    {
        // TODO: 实现ClickHouse查询逻辑
        // 这里返回空数据作为占位
        return $this->success([
            'data' => [],
            'total' => 0,
            'message' => '历史归档查询功能开发中'
        ]);
    }

    public function groupByTrigger(Request $request, string $taskHashId): JsonResponse
    {
        $query = TaskLog::where('task_id', $taskHashId)
            ->selectRaw('trigger_id, MAX(created_at) as latest_at, status, COUNT(*) as count')
            ->groupBy('trigger_id', 'status');

        if ($request->filled('status')) {
            $query->having('status', $request->status);
        }

        $groups = $query->orderBy('latest_at', 'desc')->paginate($request->get('per_page', 15));

        return $this->success($groups);
    }
}
