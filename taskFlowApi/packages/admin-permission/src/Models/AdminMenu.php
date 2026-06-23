<?php

namespace Admin\Permission\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Admin\Permission\Traits\HasHashId;

class AdminMenu extends Model
{
    use SoftDeletes, HasHashId;

    protected $table = 'admin_menus';

    protected $primaryKey   = 'hash_id';
    protected $keyType      = 'string';
    public    $incrementing = false;

    protected $dateFormat = 'Y-m-d H:i:s';

    protected $fillable = [
        'parent_id', 'name', 'icon', 'path', 'component', 'sort',
        'type', 'status', 'is_link', 'keep_alive', 'description'
    ];

    protected $casts = [
        'type'       => 'integer',
        'status'     => 'integer',
        'is_link'    => 'integer',
        'keep_alive' => 'integer',
    ];

    const TYPE_DIRECTORY = 1;
    const TYPE_MENU      = 2;
    const TYPE_BUTTON    = 3;

    public function parent()
    {
        return $this->belongsTo(AdminMenu::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(AdminMenu::class, 'parent_id')->orderBy('sort');
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(AdminRole::class, 'admin_role_menu', 'menu_id', 'role_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function scopeMenu($query)
    {
        return $query->where('type', self::TYPE_MENU);
    }

    public function scopeDirectory($query)
    {
        return $query->where('type', self::TYPE_DIRECTORY);
    }

    public function scopeButton($query)
    {
        return $query->where('type', self::TYPE_BUTTON);
    }
}
