<?php

namespace App\Models;

use App\Traits\HasHashId;

class SystemSetting extends BaseModel
{
    use HasHashId;

    protected $table = 'system_settings';

    protected $primaryKey = 'hash_id';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'key', 'value', 'description'
    ];

    protected $casts = [
        'updated_at' => 'datetime:Y-m-d H:i:s'
    ];
}
