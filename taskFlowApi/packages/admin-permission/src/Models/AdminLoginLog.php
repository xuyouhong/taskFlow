<?php

namespace Admin\Permission\Models;

use Illuminate\Database\Eloquent\Model;
use Admin\Permission\Traits\HasHashId;

class AdminLoginLog extends Model
{
    use HasHashId;

    protected $table = 'admin_login_logs';

    protected $primaryKey   = 'hash_id';
    protected $keyType      = 'string';
    public    $incrementing = false;

    public $timestamps = false;

    protected $dateFormat = 'Y-m-d H:i:s';

    protected $fillable = [
        'user_id', 'username', 'ip', 'user_agent', 'browser',
        'os', 'device', 'country', 'region', 'city',
        'login_at', 'logout_at', 'online_duration', 'status', 'message'
    ];

    protected $casts = [
        'login_at'  => 'datetime',
        'logout_at' => 'datetime',
        'status'    => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(AdminUser::class, 'user_id');
    }
}
