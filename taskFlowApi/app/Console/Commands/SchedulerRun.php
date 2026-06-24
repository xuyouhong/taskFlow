<?php

namespace App\Console\Commands;

use App\Jobs\ExecuteTask;
use App\Models\Task;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SchedulerRun extends Command
{
    protected $signature = 'scheduler:run';

    protected $description = '扫描定时任务并触发到期的任务';

    public function handle(): int
    {
        Log::info('[SchedulerRun] 开始扫描定时任务');

        $tasks = Task::where('status', 'enabled')
            ->whereNotNull('cron_expression')
            ->where(function ($q) {
                $q->whereNull('next_run_at')
                    ->orWhere('next_run_at', '<=', now());
            })
            ->orderBy('priority', 'desc')
            ->orderBy('next_run_at', 'asc')
            ->get();

        if ($tasks->isEmpty()) {
            Log::info('[SchedulerRun] 没有到期的任务');
            return self::SUCCESS;
        }

        $triggeredCount = 0;

        foreach ($tasks as $task) {
            try {
                DB::beginTransaction();

                $task = Task::where('hash_id', $task->hash_id)
                    ->lockForUpdate()
                    ->first();

                if (!$task || $task->status !== 'enabled') {
                    DB::rollBack();
                    continue;
                }

                if (!$task->isDueToRun() && $task->next_run_at) {
                    DB::rollBack();
                    continue;
                }

                if ($task->concurrency_strategy === 'forbid') {
                    $runningCount = $task->taskLogs()->where('status', 'running')->count();
                    if ($runningCount > 0) {
                        Log::info("[SchedulerRun] 任务 {$task->name} ({$task->hash_id}) 已有运行中的实例，跳过");
                        $task->calculateNextRunAt();
                        $task->save();
                        DB::commit();
                        continue;
                    }
                }

                dispatch(new ExecuteTask($task->hash_id, 'schedule'));

                $task->calculateNextRunAt();
                $task->save();

                DB::commit();

                $triggeredCount++;
                Log::info("[SchedulerRun] 已触发任务: {$task->name} ({$task->hash_id}), 下次执行: {$task->next_run_at}");
            } catch (\Throwable $e) {
                DB::rollBack();
                Log::error("[SchedulerRun] 触发任务失败: {$task->name} ({$task->hash_id}), 错误: {$e->getMessage()}");
            }
        }

        Log::info("[SchedulerRun] 扫描完成，共触发 {$triggeredCount} 个任务");

        return self::SUCCESS;
    }
}
