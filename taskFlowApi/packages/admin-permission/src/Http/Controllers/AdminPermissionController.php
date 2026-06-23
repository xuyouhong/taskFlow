<?php

namespace Admin\Permission\Http\Controllers;

use Illuminate\Http\Request;
use Admin\Permission\Models\AdminPermission;
use Admin\Permission\Http\Requests\AdminPermissionRequest;

class AdminPermissionController extends AdminController
{
    public function index(Request $request)
    {
        $query = AdminPermission::query();

        if ($request->has('name') && $request->name) {
            $query->where('name', 'like', "%{$request->name}%");
        }

        if ($request->has('slug') && $request->slug) {
            $query->where('slug', 'like', "%{$request->slug}%");
        }

        $permissions = $query->orderBy('sort')->orderBy('_seq')->paginate($request->get('per_page', 15));

        return $this->paginate($permissions);
    }

    public function store(AdminPermissionRequest $request)
    {
        $permission = AdminPermission::create($request->validated());
        return $this->success($permission->toArray(), '权限创建成功');
    }

    public function show($hashId)
    {
        $permission = AdminPermission::findOrFail($hashId);
        return $this->success($permission->toArray());
    }

    public function update(AdminPermissionRequest $request, $hashId)
    {
        $permission = AdminPermission::findOrFail($hashId);
        $permission->update($request->validated());
        return $this->success($permission->toArray(), '权限更新成功');
    }

    public function destroy($hashId)
    {
        $permission = AdminPermission::findOrFail($hashId);
        $permission->delete();
        return $this->success([], '权限删除成功');
    }

    public function batchUpdateStatus(Request $request)
    {
        $request->validate([
            'ids'    => 'required|array',
            'status' => 'required|in:0,1'
        ]);

        AdminPermission::whereIn('hash_id', $request->ids)->update(['status' => $request->status]);

        return $this->success([], '状态更新成功');
    }

    public function syncRoutes(Request $request)
    {
        $routes = app('router')->getRoutes();

        foreach ($routes as $route) {
            if ($route->getName() && str_starts_with($route->getName(), 'admin.')) {
                $methods = $route->methods();
                $method  = in_array('GET', $methods) ? 'GET' : implode(',', $methods);

                AdminPermission::updateOrCreate(
                    ['slug' => $route->getName()],
                    [
                        'name'        => $this->getPermissionName($route->getName()),
                        'http_method' => $method,
                        'http_path'   => $route->uri(),
                        'description' => $this->getPermissionName($route->getName()),
                        'status'      => 1,
                        'sort'        => 0,
                    ]
                );
            }
        }

        return $this->success([], '路由同步成功');
    }

    private function getPermissionName($slug)
    {
        $names = [
            'admin.users.index'   => '用户列表',
            'admin.users.create'  => '创建用户',
            'admin.users.store'   => '保存用户',
            'admin.users.show'    => '用户详情',
            'admin.users.edit'    => '编辑用户',
            'admin.users.update'  => '更新用户',
            'admin.users.destroy' => '删除用户',
        ];

        return $names[$slug] ?? str_replace(['admin.', '.'], ['', ' '], $slug);
    }
}
