<?php

namespace Admin\Permission\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Admin\Permission\Traits\HasHashId;

class AdminPermission extends Model
{
    use SoftDeletes, HasHashId;

    protected $table = 'admin_permissions';

    protected $primaryKey   = 'hash_id';
    protected $keyType      = 'string';
    public    $incrementing = false;

    protected $dateFormat = 'Y-m-d H:i:s';

    protected $fillable = [
        'name', 'slug', 'http_method', 'http_path', 'description', 'status', 'sort'
    ];

    protected $casts = [
        'status' => 'integer',
        'sort'   => 'integer',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(AdminRole::class, 'admin_role_permission', 'permission_id', 'role_id');
    }
}
