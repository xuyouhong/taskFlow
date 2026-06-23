<?php

namespace Admin\Permission\Models;

use Illuminate\Database\Eloquent\Model;
use Admin\Permission\Traits\HasHashId;

class AdminOperationLog extends Model
{
    use HasHashId;

    protected $table = 'admin_operation_logs';

    protected $primaryKey   = 'hash_id';
    protected $keyType      = 'string';
    public    $incrementing = false;

    public $timestamps = false;

    protected $dateFormat = 'Y-m-d H:i:s';

    protected $fillable = [
        'user_id', 'username', 'method', 'path', 'params',
        'response', 'ip', 'user_agent', 'status_code', 'duration', 'operated_at'
    ];

    protected $casts = [
        'params'      => 'array',
        'response'    => 'array',
        'operated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(AdminUser::class, 'user_id');
    }
}
