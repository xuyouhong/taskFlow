<?php

namespace App\Models;

use App\Traits\HasHashId;
use Cron\CronExpression;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Task extends BaseModel
{
    use SoftDeletes, HasHashId;

    protected $table = 'tasks';

    protected $primaryKey = 'hash_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'project_id', 'name', 'description', 'cron_expression', 'timezone',
        'executor_type', 'executor_config', 'retry_times', 'retry_interval',
        'timeout', 'concurrency_strategy', 'misfire_strategy', 'priority',
        'status', 'last_run_at', 'next_run_at', 'last_run_status', 'created_by'
    ];

    protected $casts = [
        'executor_config' => 'array',
        'retry_times' => 'integer',
        'retry_interval' => 'integer',
        'timeout' => 'integer',
        'priority' => 'integer',
        'last_run_at' => 'datetime:Y-m-d H:i:s',
        'next_run_at' => 'datetime:Y-m-d H:i:s',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
        'deleted_at' => 'datetime:Y-m-d H:i:s'
    ];

    protected $appends = [
        'next_run_at_local',
        'last_run_at_local',
    ];

    public function getNextRunAtLocalAttribute(): ?string
    {
        if (!$this->next_run_at) {
            return null;
        }
        $timezone = $this->timezone ?: 'Asia/Shanghai';
        return $this->next_run_at->tz($timezone)->format('Y-m-d H:i:s');
    }

    public function getLastRunAtLocalAttribute(): ?string
    {
        if (!$this->last_run_at) {
            return null;
        }
        $timezone = $this->timezone ?: 'Asia/Shanghai';
        return $this->last_run_at->tz($timezone)->format('Y-m-d H:i:s');
    }

    protected static function booted(): void
    {
        static::creating(function (self $task) {
            $task->calculateNextRunAt();
        });

        static::updating(function (self $task) {
            if ($task->isDirty('cron_expression') || $task->isDirty('timezone') || $task->isDirty('status')) {
                $task->calculateNextRunAt();
            }
        });
    }

    public function calculateNextRunAt(): void
    {
        if ($this->status !== 'enabled' || empty($this->cron_expression)) {
            $this->next_run_at = null;
            return;
        }

        try {
            $cronExpression = trim($this->cron_expression);
            $parts = preg_split('/\s+/', $cronExpression);
            $timezone = $this->timezone ?: 'Asia/Shanghai';
            $now = now()->tz($timezone);

            if (count($parts) === 6) {
                $secondExpr = $parts[0];
                $fivePartExpr = implode(' ', array_slice($parts, 1));
                $cron = new CronExpression($fivePartExpr);

                $nextSecond = $this->findNextSecondInCurrentMinute($secondExpr, $now);
                if ($nextSecond && $cron->isDue($nextSecond->toDateTime(), $timezone)) {
                    $this->next_run_at = $nextSecond->copy();
                } else {
                    $nextMinute = $cron->getNextRunDate($now->toDateTime(), 0, false, $timezone);
                    $nextMinute = \Carbon\Carbon::instance($nextMinute)->tz($timezone);
                    $firstSecond = $this->findFirstSecondInMinute($secondExpr, $nextMinute);
                    $this->next_run_at = $firstSecond ? $firstSecond->copy() : null;
                }
            } else {
                $cron = new CronExpression($cronExpression);
                $nextRun = $cron->getNextRunDate($now->toDateTime(), 0, false, $timezone);
                $this->next_run_at = \Carbon\Carbon::instance($nextRun)->tz($timezone);
            }
        } catch (\Throwable $e) {
            $this->next_run_at = null;
        }
    }

    private function findNextSecondInCurrentMinute(string $secondExpr, \Carbon\Carbon $now): ?\Carbon\Carbon
    {
        $seconds = $this->parseCronField($secondExpr, 0, 59);
        $currentSecond = (int) $now->format('s');

        foreach ($seconds as $sec) {
            if ($sec >= $currentSecond) {
                $dt = $now->copy();
                $dt->setTime((int) $dt->format('H'), (int) $dt->format('i'), $sec);
                return $dt;
            }
        }

        return null;
    }

    private function findFirstSecondInMinute(string $secondExpr, \Carbon\Carbon $minute): ?\Carbon\Carbon
    {
        $seconds = $this->parseCronField($secondExpr, 0, 59);
        if (empty($seconds)) {
            return null;
        }

        $firstSecond = min($seconds);
        $dt = $minute->copy();
        $dt->setTime((int) $dt->format('H'), (int) $dt->format('i'), $firstSecond);
        return $dt;
    }

    private function parseCronField(string $expr, int $min, int $max): array
    {
        $result = [];
        $expr = trim($expr);

        if ($expr === '*') {
            return range($min, $max);
        }

        $parts = explode(',', $expr);
        foreach ($parts as $part) {
            $part = trim($part);
            if (str_contains($part, '/')) {
                [$range, $step] = explode('/', $part, 2);
                $step = (int) $step;
                if ($range === '*') {
                    $start = $min;
                    $end = $max;
                } elseif (str_contains($range, '-')) {
                    [$start, $end] = explode('-', $range, 2);
                    $start = (int) $start;
                    $end = (int) $end;
                } else {
                    $start = (int) $range;
                    $end = $max;
                }
                for ($i = $start; $i <= $end; $i += $step) {
                    $result[] = $i;
                }
            } elseif (str_contains($part, '-')) {
                [$start, $end] = explode('-', $part, 2);
                $start = (int) $start;
                $end = (int) $end;
                for ($i = $start; $i <= $end; $i++) {
                    $result[] = $i;
                }
            } else {
                $result[] = (int) $part;
            }
        }

        $result = array_unique($result);
        sort($result);
        return $result;
    }

    public function isDueToRun(): bool
    {
        if ($this->status !== 'enabled' || empty($this->cron_expression)) {
            return false;
        }

        if (!$this->next_run_at) {
            return false;
        }

        return now()->gte($this->next_run_at);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'created_by', 'hash_id');
    }

    public function taskLogs(): HasMany
    {
        return $this->hasMany(TaskLog::class, 'task_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(TaskNotification::class, 'task_id');
    }
}
