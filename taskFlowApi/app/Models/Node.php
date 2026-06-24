<?php

namespace App\Models;

use App\Traits\HasHashId;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Node extends BaseModel
{
    use HasHashId;

    protected $table = 'nodes';

    protected $primaryKey = 'hash_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name', 'ip', 'agent_port', 'hostname', 'agent_token',
        'allowed_command_prefix', 'status', 'last_heartbeat_at',
        'cpu_cores', 'memory_total_mb', 'agent_version'
    ];

    protected $hidden = [
        'agent_token'
    ];

    protected $casts = [
        'agent_port' => 'integer',
        'cpu_cores' => 'integer',
        'memory_total_mb' => 'integer',
        'last_heartbeat_at' => 'datetime:Y-m-d H:i:s',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s'
    ];

    public function taskLogs(): HasMany
    {
        return $this->hasMany(TaskLog::class, 'node_id');
    }
}
