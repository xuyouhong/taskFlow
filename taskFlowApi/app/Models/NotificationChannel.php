<?php

namespace App\Models;

use App\Traits\HasHashId;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NotificationChannel extends BaseModel
{
    use HasHashId;

    protected $table = 'notification_channels';

    protected $primaryKey = 'hash_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name', 'type', 'config', 'status'
    ];

    protected $casts = [
        'config' => 'array',
        'status' => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s'
    ];

    public function taskNotifications(): HasMany
    {
        return $this->hasMany(TaskNotification::class, 'channel_id');
    }

    public function notificationLogs(): HasMany
    {
        return $this->hasMany(NotificationLog::class, 'channel_id');
    }
}
