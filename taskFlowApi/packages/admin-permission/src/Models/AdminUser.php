<?php

namespace Admin\Permission\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Admin\Permission\Traits\HasHashId;

class AdminUser extends Authenticatable
{
    use SoftDeletes, HasApiTokens, Notifiable, HasHashId;

    protected $table = 'admin_users';

    protected $primaryKey   = 'hash_id';
    protected $keyType      = 'string';
    public    $incrementing = false;

    protected $dateFormat = 'Y-m-d H:i:s';

    protected $fillable = [
        'username', 'email', 'password', 'real_name', 'avatar',
        'phone', 'status', 'last_login_at', 'last_login_ip'
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'status'        => 'integer',
        'last_login_at' => 'datetime:Y-m-d H:i:s',
        'created_at'    => 'datetime:Y-m-d H:i:s',
        'updated_at'    => 'datetime:Y-m-d H:i:s',
        'deleted_at'    => 'datetime:Y-m-d H:i:s'
    ];

    public function getAuthIdentifierName()
    {
        return 'username';
    }

    public function getAuthIdentifier()
    {
        return $this->username;
    }

    public function getAuthPasswordName()
    {
        return 'password';
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(AdminRole::class, 'admin_user_role', 'user_id', 'role_id');
    }

    /**
     * 获取用户所有权限（通过角色聚合，请求内缓存）。
     */
    protected $cachedPermissions = null;

    public function permissions()
    {
        if ($this->cachedPermissions === null) {
            $roles                   = $this->relationLoaded('roles') ? $this->roles : $this->roles()->with('permissions')->get();
            $this->cachedPermissions = $roles->pluck('permissions')->flatten()->unique('hash_id')->values();
        }
        return $this->cachedPermissions;
    }

    public function hasRole($role): bool
    {
        if (is_string($role)) {
            return $this->roles->contains('slug', $role);
        }
        if ($role instanceof AdminRole) {
            return $this->roles->contains('hash_id', $role->hash_id);
        }
        return $role->intersect($this->roles)->isNotEmpty();
    }

    public function hasPermission($permission): bool
    {
        if (is_string($permission)) {
            return $this->permissions()->contains('slug', $permission);
        }
        return $permission->intersect($this->permissions())->isNotEmpty();
    }

    public function loginLogs()
    {
        return $this->hasMany(AdminLoginLog::class, 'user_id');
    }

    public function operationLogs()
    {
        return $this->hasMany(AdminOperationLog::class, 'user_id');
    }

    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }
}
