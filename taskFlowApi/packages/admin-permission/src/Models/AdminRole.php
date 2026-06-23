<?php

namespace Admin\Permission\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Admin\Permission\Traits\HasHashId;

class AdminRole extends Model
{
    use SoftDeletes, HasHashId;

    protected $table = 'admin_roles';

    protected $primaryKey   = 'hash_id';
    protected $keyType      = 'string';
    public    $incrementing = false;

    protected $dateFormat = 'Y-m-d H:i:s';

    protected $fillable = [
        'name', 'slug', 'description', 'status', 'sort'
    ];

    protected $casts = [
        'status' => 'integer',
        'sort'   => 'integer',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(AdminUser::class, 'admin_user_role', 'role_id', 'user_id');
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(AdminPermission::class, 'admin_role_permission', 'role_id', 'permission_id');
    }

    public function menus(): BelongsToMany
    {
        return $this->belongsToMany(AdminMenu::class, 'admin_role_menu', 'role_id', 'menu_id');
    }
}
