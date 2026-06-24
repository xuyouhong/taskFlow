<?php

namespace App\Models;

use App\Traits\HasHashId;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends BaseModel
{
    use HasHashId;

    protected $table = 'notification_logs';

    protected $primaryKey = 'hash_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'task_log_id', 'channel_id', 'status', 'content', 'error_message', 'sent_at'
    ];

    protected $casts = [
        'sent_at' => 'datetime:Y-m-d H:i:s',
        'created_at' => 'datetime:Y-m-d H:i:s'
    ];

    public function taskLog(): BelongsTo
    {
        return $this->belongsTo(TaskLog::class, 'task_log_id');
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(NotificationChannel::class, 'channel_id');
    }
}
