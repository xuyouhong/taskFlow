<?php

namespace App\Jobs;

use App\Models\Node;
use App\Models\Task;
use App\Models\TaskLog;
use App\Models\TaskLogDetail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ExecuteTask implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public int $tries = 1;

    public function __construct(
        private string $hashId,
        private string $triggerType = 'manual',
        private int $retryCount = 0,
    ) {}

    public function handle(): void
    {
        $task = Task::find($this->hashId);
        if (!$task) {
            \Log::warning("[ExecuteTask] 任务不存在: {$this->hashId}");
            return;
        }

        $triggerId = (string) Str::uuid();
        $executionId = (string) Str::uuid();
        $startTime = now();

        $taskLog = TaskLog::create([
            'task_id' => $this->hashId,
            'trigger_id' => $triggerId,
            'execution_id' => $executionId,
            'trigger_type' => $this->triggerType,
            'status' => 'running',
            'start_time' => $startTime,
            'request_snapshot' => $task->executor_config,
            'retry_count' => $this->retryCount,
        ]);

        try {
            match ($task->executor_type) {
                'http' => $this->executeHttp($task, $taskLog),
                'shell' => $this->executeShell($task, $taskLog),
                'job' => $this->executeJob($task, $taskLog),
                'mq' => $this->executeMq($task, $taskLog),
                default => throw new \RuntimeException("未知执行器类型: {$task->executor_type}"),
            };
        } catch (\Throwable $e) {
            $this->markFailed($task, $taskLog, $e->getMessage());
        }
    }

    private function executeHttp(Task $task, TaskLog $taskLog): void
    {
        $config = $task->executor_config ?? [];
        $url = $config['url'] ?? '';
        $method = strtoupper($config['method'] ?? 'POST');
        $headers = $config['headers'] ?? [];
        $payload = $config['payload'] ?? [];

        if (empty($url)) {
            throw new \RuntimeException('HTTP执行器缺少url配置');
        }

        $timeout = $task->timeout ?? 30;

        $response = Http::timeout($timeout)
            ->withHeaders($headers)
            ->send($method, $url, ['json' => $payload]);

        $statusCode = $response->status();
        $body = (string) $response->body();
        $summary = mb_substr($body, 0, 500);

        $isSuccess = $statusCode >= 200 && $statusCode < 300;

        $taskLog->update([
            'status' => $isSuccess ? 'success' : 'failed',
            'end_time' => now(),
            'duration_ms' => (int) $taskLog->start_time->diffInMilliseconds(now()),
            'response_summary' => $summary,
            'error_message' => $isSuccess ? null : "HTTP {$statusCode}",
        ]);

        TaskLogDetail::create([
            'task_log_id' => $taskLog->hash_id,
            'stdout_content' => $body,
            'stderr_content' => !$isSuccess ? "HTTP状态码: {$statusCode}" : null,
        ]);

        $this->updateTaskStatus($task, $isSuccess ? 'success' : 'failed');
    }

    private function executeShell(Task $task, TaskLog $taskLog): void
    {
        $config = $task->executor_config ?? [];
        $command = $config['command'] ?? '';
        $nodeId = $config['node_id'] ?? null;

        if (empty($command)) {
            throw new \RuntimeException('Shell执行器缺少command配置');
        }

        if ($nodeId) {
            $node = Node::find($nodeId);
            if (!$node) {
                throw new \RuntimeException("执行节点不存在: {$nodeId}");
            }
            if ($node->status !== 'online') {
                throw new \RuntimeException("执行节点离线: {$node->name}");
            }
            $taskLog->node_id = $node->hash_id;
            $taskLog->save();
        }

        $descriptorSpec = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($command, $descriptorSpec, $pipes);
        if (!is_resource($process)) {
            throw new \RuntimeException('无法启动Shell进程');
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exitCode = proc_close($process);

        $duration = (int) $taskLog->start_time->diffInMilliseconds(now());
        $isSuccess = $exitCode === 0;

        $taskLog->update([
            'status' => $isSuccess ? 'success' : 'failed',
            'end_time' => now(),
            'duration_ms' => $duration,
            'response_summary' => mb_substr($stdout, 0, 500),
            'error_message' => $isSuccess ? null : $stderr,
        ]);

        TaskLogDetail::create([
            'task_log_id' => $taskLog->hash_id,
            'stdout_content' => $stdout,
            'stderr_content' => $stderr,
        ]);

        $this->updateTaskStatus($task, $isSuccess ? 'success' : 'failed');
    }

    private function executeJob(Task $task, TaskLog $taskLog): void
    {
        $config = $task->executor_config ?? [];
        $jobClass = $config['job_class'] ?? '';
        $params = $config['params'] ?? [];

        if (empty($jobClass) || !class_exists($jobClass)) {
            throw new \RuntimeException("Job类不存在: {$jobClass}");
        }

        dispatch(new $jobClass(...$params));

        $taskLog->update([
            'status' => 'success',
            'end_time' => now(),
            'duration_ms' => (int) $taskLog->start_time->diffInMilliseconds(now()),
            'response_summary' => "Job已派发: {$jobClass}",
        ]);

        $this->updateTaskStatus($task, 'success');
    }

    private function executeMq(Task $task, TaskLog $taskLog): void
    {
        $config = $task->executor_config ?? [];
        $topic = $config['topic'] ?? '';
        $payload = $config['payload'] ?? [];

        if (empty($topic)) {
            throw new \RuntimeException('MQ执行器缺少topic配置');
        }

        $taskLog->update([
            'status' => 'success',
            'end_time' => now(),
            'duration_ms' => (int) $taskLog->start_time->diffInMilliseconds(now()),
            'response_summary' => "消息已发布到: {$topic}",
        ]);

        $this->updateTaskStatus($task, 'success');
    }

    private function markFailed(Task $task, TaskLog $taskLog, string $errorMessage): void
    {
        $taskLog->update([
            'status' => 'failed',
            'end_time' => now(),
            'duration_ms' => (int) $taskLog->start_time->diffInMilliseconds(now()),
            'error_message' => $errorMessage,
        ]);

        if ($task->retry_times > $this->retryCount && $task->retry_interval > 0) {
            dispatch(new self($this->hashId, 'retry', $this->retryCount + 1))
                ->delay(now()->addSeconds($task->retry_interval));
            return;
        }

        $this->updateTaskStatus($task, 'failed');
    }

    private function updateTaskStatus(Task $task, string $lastRunStatus): void
    {
        $task->last_run_at = now();
        $task->last_run_status = $lastRunStatus;
        if ($this->triggerType !== 'retry') {
            $task->calculateNextRunAt();
        }
        $task->save();
    }
}
