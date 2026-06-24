<?php

namespace App\Models;

use App\Traits\HasHashId;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskNotification extends BaseModel
{
    use HasHashId;

    protected $table = 'task_notifications';

    protected $primaryKey = 'hash_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'task_id', 'channel_id', 'notify_on', 'consecutive_failures_threshold'
    ];

    protected $casts = [
        'consecutive_failures_threshold' => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s'
    ];

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'task_id');
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(NotificationChannel::class, 'channel_id');
    }
}
