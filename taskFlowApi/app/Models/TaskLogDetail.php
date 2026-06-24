<?php

namespace App\Models;

use App\Traits\HasHashId;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskLogDetail extends BaseModel
{
    use HasHashId;

    protected $table = 'task_log_details';

    protected $primaryKey = 'hash_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'task_log_id', 'stdout_content', 'stderr_content'
    ];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s'
    ];

    public function taskLog(): BelongsTo
    {
        return $this->belongsTo(TaskLog::class, 'task_log_id');
    }
}
