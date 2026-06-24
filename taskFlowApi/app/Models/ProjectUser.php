<?php

namespace App\Models;

use App\Traits\HasHashId;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectUser extends BaseModel
{
    use HasHashId;

    protected $table = 'project_user';

    protected $primaryKey = 'hash_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'project_id', 'user_id', 'role'
    ];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s'
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'user_id');
    }
}
