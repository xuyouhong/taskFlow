<?php

namespace App\Console\Commands;

use App\Jobs\ExecuteTask;
use App\Models\Task;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SchedulerDaemon extends Command
{
    protected $signature = 'scheduler:daemon {--sleep=1 : 每次扫描间隔秒数}';

    protected $description = '定时任务调度守护进程（秒级精度）';

    protected bool $running = true;

    public function handle(): int
    {
        $sleep = (int) $this->option('sleep');
        $sleep = max(1, min(60, $sleep));

        Log::info('[SchedulerDaemon] 启动调度守护进程，间隔: ' . $sleep . '秒');

        $this->info('调度守护进程已启动，按 Ctrl+C 停止');
        $this->info('扫描间隔: ' . $sleep . ' 秒');

        pcntl_async_signals(true);
        pcntl_signal(SIGTERM, function () {
            $this->running = false;
            Log::info('[SchedulerDaemon] 收到 SIGTERM 信号，正在停止...');
        });
        pcntl_signal(SIGINT, function () {
            $this->running = false;
            Log::info('[SchedulerDaemon] 收到 SIGINT 信号，正在停止...');
        });

        $lastMinute = -1;

        while ($this->running) {
            $startTime = microtime(true);

            try {
                $this->runScheduler();
            } catch (\Throwable $e) {
                Log::error('[SchedulerDaemon] 调度异常: ' . $e->getMessage());
                $this->error('调度异常: ' . $e->getMessage());
            }

            $elapsed = microtime(true) - $startTime;
            $sleepTime = $sleep - $elapsed;

            if ($sleepTime > 0) {
                usleep((int) ($sleepTime * 1000000));
            }
        }

        Log::info('[SchedulerDaemon] 调度守护进程已停止');
        $this->info('调度守护进程已停止');

        return self::SUCCESS;
    }

    private function runScheduler(): void
    {
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
            return;
        }

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
                    $timeoutMinutes = (int) ($task->timeout_minutes ?? 120);
                    $task->taskLogs()
                        ->where('status', 'running')
                        ->where('created_at', '<', now()->subMinutes($timeoutMinutes))
                        ->update([
                            'status' => 'failed',
                            'error_message' => '任务超时自动重置：执行时间超过 ' . $timeoutMinutes . ' 分钟',
                            'updated_at' => now(),
                        ]);

                    $runningCount = $task->taskLogs()->where('status', 'running')->count();
                    if ($runningCount > 0) {
                        Log::info("[SchedulerDaemon] 任务 {$task->name} ({$task->hash_id}) 已有运行中的实例，跳过");
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

                Log::info("[SchedulerDaemon] 已触发任务: {$task->name} ({$task->hash_id}), 下次执行: {$task->next_run_at}");
            } catch (\Throwable $e) {
                DB::rollBack();
                Log::error("[SchedulerDaemon] 触发任务失败: {$task->name} ({$task->hash_id}), 错误: {$e->getMessage()}");
            }
        }
    }
}
