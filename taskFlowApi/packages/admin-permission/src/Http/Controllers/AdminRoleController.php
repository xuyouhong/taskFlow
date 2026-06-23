<?php

namespace Admin\Permission\Http\Controllers;

use Illuminate\Http\Request;
use Admin\Permission\Models\AdminRole;
use Admin\Permission\Models\AdminPermission;
use Admin\Permission\Models\AdminMenu;
use Admin\Permission\Http\Requests\AdminRoleRequest;

class AdminRoleController extends AdminController
{
    public function index(Request $request)
    {
        $query = AdminRole::with(['permissions', 'menus']);

        if ($request->has('name') && $request->name) {
            $query->where('name', 'like', "%{$request->name}%");
        }

        if ($request->has('slug') && $request->slug) {
            $query->where('slug', 'like', "%{$request->slug}%");
        }

        $roles = $query->orderBy('sort')->orderBy('_seq')->paginate($request->get('per_page', 15));

        return $this->paginate($roles);
    }

    public function store(AdminRoleRequest $request)
    {
        $role = AdminRole::create($request->validated());

        if ($request->has('permission_ids')) {
            $role->permissions()->sync($request->permission_ids);
        }

        if ($request->has('menu_ids')) {
            $role->menus()->sync($request->menu_ids);
        }

        return $this->success($role->toArray(), '角色创建成功');
    }

    public function show($hashId)
    {
        $role = AdminRole::findOrFail($hashId);
        $role->load(['permissions', 'menus']);
        return $this->success($role->toArray());
    }

    public function update(AdminRoleRequest $request, $hashId)
    {
        $role = AdminRole::findOrFail($hashId);
        $role->update($request->validated());

        if ($request->has('permission_ids')) {
            $role->permissions()->sync($request->permission_ids);
        }

        if ($request->has('menu_ids')) {
            $role->menus()->sync($request->menu_ids);
        }

        return $this->success($role->toArray(), '角色更新成功');
    }

    public function destroy($hashId)
    {
        $role = AdminRole::findOrFail($hashId);

        if ($role->slug === 'super-admin') {
            return $this->error('超级管理员角色不能删除');
        }

        $role->delete();
        return $this->success([], '角色删除成功');
    }

    public function permissions()
    {
        $permissions = AdminPermission::where('status', 1)->get();
        return $this->success($permissions->toArray());
    }

    public function menus()
    {
        $menus = AdminMenu::with([
            'children' => function ($query) {
                $query->active()->orderBy('sort')->with([
                    'children' => function ($subQuery) {
                        $subQuery->active()->orderBy('sort');
                    }
                ]);
            }
        ])
            ->whereNull('parent_id')
            ->where('status', 1)
            ->orderBy('sort')
            ->get();

        return $this->success($menus->toArray());
    }

    public function roleMenus($hashId)
    {
        $role = AdminRole::findOrFail($hashId);
        $role->load('menus');
        return $this->success($role->menus->toArray());
    }

    public function rolePermissions($hashId)
    {
        $role = AdminRole::findOrFail($hashId);
        $role->load('permissions');
        return $this->success($role->permissions->toArray());
    }

    public function batchUpdateStatus(Request $request)
    {
        $request->validate([
            'ids'    => 'required|array',
            'status' => 'required|in:0,1'
        ]);

        AdminRole::whereIn('hash_id', $request->ids)
            ->where('slug', '!=', 'super-admin')
            ->update(['status' => $request->status]);

        return $this->success([], '状态更新成功');
    }

    public function assignPermissionsToRole(Request $request, $roleHashId)
    {
        $request->validate([
            'permission_ids'   => 'required|array',
            'permission_ids.*' => 'string'
        ]);

        $role = AdminRole::findOrFail($roleHashId);

        if ($role->slug === 'super-admin') {
            return $this->error('超级管理员角色权限不能单独分配');
        }

        $role->permissions()->sync($request->permission_ids);

        return $this->success([], '权限分配成功');
    }

    public function assignMenusToRole(Request $request, $roleHashId)
    {
        $request->validate([
            'menu_ids'   => 'required|array',
            'menu_ids.*' => 'string'
        ]);

        $role = AdminRole::findOrFail($roleHashId);

        if ($role->slug === 'super-admin') {
            return $this->error('超级管理员角色菜单不能单独分配');
        }

        $role->menus()->sync($request->menu_ids);

        return $this->success([], '菜单分配成功');
    }
}
