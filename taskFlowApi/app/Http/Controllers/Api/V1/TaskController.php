<?php

namespace App\Http\Controllers\Api\V1;

use App\Jobs\ExecuteTask;
use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TaskController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = Task::with(['project', 'creator']);

        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('executor_type')) {
            $query->where('executor_type', $request->executor_type);
        }

        if ($request->filled('keyword')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->keyword}%")
                    ->orWhere('description', 'like', "%{$request->keyword}%");
            });
        }

        $tasks = $query->orderBy('priority', 'desc')
            ->orderBy('next_run_at', 'asc')
            ->paginate($request->get('per_page', 15));

        return $this->success($tasks);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'project_id' => 'required|string',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'cron_expression' => 'required|string|max:50',
            'timezone' => 'nullable|string|max:50',
            'executor_type' => 'required|in:http,shell,job,mq',
            'executor_config' => 'required|array',
            'retry_times' => 'nullable|integer|min:0|max:10',
            'retry_interval' => 'nullable|integer|min:1|max:3600',
            'timeout' => 'nullable|integer|min:1|max:86400',
            'concurrency_strategy' => 'nullable|in:allow,forbid,replace',
            'misfire_strategy' => 'nullable|in:skip,fire_once,fire_all',
            'priority' => 'nullable|integer|min:-100|max:100',
            'status' => 'nullable|in:enabled,disabled,paused',
        ]);

        $user = $request->user();

        $task = Task::create([
            'project_id' => $request->project_id,
            'name' => $request->name,
            'description' => $request->description,
            'cron_expression' => $request->cron_expression,
            'timezone' => $request->timezone ?? 'Asia/Shanghai',
            'executor_type' => $request->executor_type,
            'executor_config' => $request->executor_config,
            'retry_times' => $request->retry_times ?? 0,
            'retry_interval' => $request->retry_interval ?? 60,
            'timeout' => $request->timeout ?? 300,
            'concurrency_strategy' => $request->concurrency_strategy ?? 'forbid',
            'misfire_strategy' => $request->misfire_strategy ?? 'skip',
            'priority' => $request->priority ?? 0,
            'status' => $request->status ?? 'enabled',
            'created_by' => $user->hash_id,
        ]);

        return $this->success($task->load(['project', 'creator']), '创建成功');
    }

    public function show(string $hashId): JsonResponse
    {
        $task = Task::with(['project', 'creator', 'notifications.channel'])->find($hashId);
        if (!$task) {
            return $this->error('任务不存在', 1002);
        }
        return $this->success($task);
    }

    public function update(Request $request, string $hashId): JsonResponse
    {
        $task = Task::find($hashId);
        if (!$task) {
            return $this->error('任务不存在', 1002);
        }

        $request->validate([
            'name' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'cron_expression' => 'nullable|string|max:50',
            'timezone' => 'nullable|string|max:50',
            'executor_type' => 'nullable|in:http,shell,job,mq',
            'executor_config' => 'nullable|array',
            'retry_times' => 'nullable|integer|min:0|max:10',
            'retry_interval' => 'nullable|integer|min:1|max:3600',
            'timeout' => 'nullable|integer|min:1|max:86400',
            'concurrency_strategy' => 'nullable|in:allow,forbid,replace',
            'misfire_strategy' => 'nullable|in:skip,fire_once,fire_all',
            'priority' => 'nullable|integer|min:-100|max:100',
            'status' => 'nullable|in:enabled,disabled,paused',
        ]);

        $task->fill($request->only([
            'name', 'description', 'cron_expression', 'timezone',
            'executor_type', 'executor_config', 'retry_times',
            'retry_interval', 'timeout', 'concurrency_strategy',
            'misfire_strategy', 'priority', 'status'
        ]));
        $task->save();

        return $this->success($task->load(['project', 'creator']), '更新成功');
    }

    public function destroy(string $hashId): JsonResponse
    {
        $task = Task::find($hashId);
        if (!$task) {
            return $this->error('任务不存在', 1002);
        }

        $task->delete();
        return $this->success(null, '删除成功');
    }

    public function trigger(Request $request, string $hashId): JsonResponse
    {
        $task = Task::find($hashId);
        if (!$task) {
            return $this->error('任务不存在', 1002);
        }

        dispatch(new ExecuteTask($task->hash_id, 'manual'));

        return $this->success(['task_id' => $task->hash_id], '触发成功');
    }

    public function pause(string $hashId): JsonResponse
    {
        $task = Task::find($hashId);
        if (!$task) {
            return $this->error('任务不存在', 1002);
        }

        $task->status = 'paused';
        $task->save();

        return $this->success($task, '已暂停');
    }

    public function resume(string $hashId): JsonResponse
    {
        $task = Task::find($hashId);
        if (!$task) {
            return $this->error('任务不存在', 1002);
        }

        $task->status = 'enabled';
        $task->save();

        return $this->success($task, '已恢复');
    }

    public function logs(Request $request, string $hashId): JsonResponse
    {
        $task = Task::find($hashId);
        if (!$task) {
            return $this->error('任务不存在', 1002);
        }

        $query = $task->taskLogs()->with('node');

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
}
