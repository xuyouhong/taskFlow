<?php

namespace App\Models;

use App\Traits\HasHashId;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends BaseModel
{
    use SoftDeletes, HasHashId;

    protected $table = 'projects';

    protected $primaryKey = 'hash_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'name', 'code', 'description', 'owner_id', 'status'
    ];

    protected $casts = [
        'status' => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
        'deleted_at' => 'datetime:Y-m-d H:i:s'
    ];

    public function owner()
    {
        return $this->belongsTo(AdminUser::class, 'owner_id', 'hash_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(AdminUser::class, 'project_user', 'project_id', 'user_id');
    }

    public function projectUsers(): HasMany
    {
        return $this->hasMany(ProjectUser::class, 'project_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'project_id');
    }
}
