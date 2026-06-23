<?php

namespace Admin\Permission\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Admin\Permission\Traits\HasHashId;

class AdminUserNotification extends Model
{
    use HasHashId;

    protected $table = 'admin_user_notifications';

    protected $primaryKey   = 'hash_id';
    protected $keyType      = 'string';
    public    $incrementing = false;

    protected $fillable = [
        'user_id', 'notification_id', 'is_read', 'read_at'
    ];

    protected $casts = [
        'is_read'    => 'boolean',
        'read_at'    => 'datetime',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'user_id');
    }

    public function notification(): BelongsTo
    {
        return $this->belongsTo(AdminNotification::class, 'notification_id');
    }

    public function markAsRead(): bool
    {
        if (!$this->is_read) {
            $this->is_read = true;
            $this->read_at = now();
            return $this->save();
        }
        return true;
    }

    public function markAsUnread(): bool
    {
        if ($this->is_read) {
            $this->is_read = false;
            $this->read_at = null;
            return $this->save();
        }
        return true;
    }

    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }
}
