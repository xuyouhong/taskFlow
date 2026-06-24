<?php

namespace App\Models;

use App\Traits\HasHashId;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TaskLog extends BaseModel
{
    use HasHashId;

    protected $table = 'task_logs';

    protected $primaryKey = 'hash_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'task_id', 'trigger_id', 'execution_id', 'trigger_type', 'status',
        'node_id', 'start_time', 'end_time', 'duration_ms', 'request_snapshot',
        'response_summary', 'error_message', 'retry_count'
    ];

    protected $casts = [
        'request_snapshot' => 'array',
        'duration_ms' => 'integer',
        'retry_count' => 'integer',
        'start_time' => 'datetime:Y-m-d H:i:s',
        'end_time' => 'datetime:Y-m-d H:i:s',
        'created_at' => 'datetime:Y-m-d H:i:s'
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function node(): BelongsTo
    {
        return $this->belongsTo(Node::class, 'node_id');
    }

    public function detail(): HasOne
    {
        return $this->hasOne(TaskLogDetail::class, 'task_log_id');
    }

    public function notificationLogs(): HasMany
    {
        return $this->hasMany(NotificationLog::class, 'task_log_id');
    }
}
