<?php

namespace Admin\Permission\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Admin\Permission\Traits\HasHashId;

class AdminNotification extends Model
{
    use HasHashId;

    protected $table = 'admin_notifications';

    protected $primaryKey   = 'hash_id';
    protected $keyType      = 'string';
    public    $incrementing = false;

    protected $fillable = [
        'title', 'content', 'type', 'priority', 'sender_id',
        'target_type', 'target_values', 'publish_time', 'expire_time', 'status'
    ];

    protected $casts = [
        'type'          => 'integer',
        'priority'      => 'integer',
        'target_type'   => 'integer',
        'target_values' => 'json',
        'publish_time'  => 'datetime:Y-m-d H:i:s',
        'expire_time'   => 'datetime:Y-m-d H:i:s',
        'status'        => 'integer',
        'created_at'    => 'datetime:Y-m-d H:i:s',
        'updated_at'    => 'datetime:Y-m-d H:i:s'
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'sender_id');
    }

    public function userNotifications(): HasMany
    {
        return $this->hasMany(AdminUserNotification::class, 'notification_id');
    }

    public function getTypeTextAttribute(): string
    {
        $typeMap = [
            1 => '通知',
            2 => '通告'
        ];
        return $typeMap[$this->type] ?? '未知';
    }

    public function getPriorityTextAttribute(): string
    {
        $priorityMap = [
            1 => '普通',
            2 => '重要',
            3 => '紧急'
        ];
        return $priorityMap[$this->priority] ?? '未知';
    }

    public function getStatusTextAttribute(): string
    {
        $statusMap = [
            1 => '草稿',
            2 => '已发布',
            3 => '已撤销'
        ];
        return $statusMap[$this->status] ?? '未知';
    }

    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }
}
