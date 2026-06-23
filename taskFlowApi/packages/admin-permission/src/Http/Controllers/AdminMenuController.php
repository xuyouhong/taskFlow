<?php

namespace Admin\Permission\Http\Controllers;

use Illuminate\Http\Request;
use Admin\Permission\Models\AdminMenu;
use Admin\Permission\Http\Requests\AdminMenuRequest;

class AdminMenuController extends AdminController
{
    public function index(Request $request)
    {
        $menus = AdminMenu::with([
            'children' => function ($query) {
                $query->orderBy('sort')->with([
                    'children' => function ($subQuery) {
                        $subQuery->orderBy('sort');
                    }
                ]);
            }
        ])
            ->whereNull('parent_id')
            ->orderBy('sort')
            ->get();

        return $this->success($menus->toArray());
    }

    public function store(AdminMenuRequest $request)
    {
        if (!$request->has('parent_id') || !$request->parent_id) {
            $request->merge(['parent_id' => null]);
        }

        $menu = AdminMenu::create($request->validated());
        return $this->success($menu->toArray(), '菜单创建成功');
    }

    public function show($hashId)
    {
        $menu = AdminMenu::findOrFail($hashId);
        return $this->success($menu->toArray());
    }

    public function update(AdminMenuRequest $request, $hashId)
    {
        $menu = AdminMenu::findOrFail($hashId);

        if (!$request->has('parent_id') || !$request->parent_id) {
            $request->merge(['parent_id' => null]);
        }

        $menu->update($request->validated());
        return $this->success($menu->toArray(), '菜单更新成功');
    }

    public function destroy($hashId)
    {
        $menu = AdminMenu::findOrFail($hashId);

        if (AdminMenu::where('parent_id', $menu->hash_id)->exists()) {
            return $this->error('请先删除子菜单');
        }

        $menu->delete();
        return $this->success([], '菜单删除成功');
    }

    public function tree()
    {
        $menus = AdminMenu::active()
            ->with([
                'children' => function ($query) {
                    $query->active()->orderBy('sort')->with([
                        'children' => function ($subQuery) {
                            $subQuery->active()->orderBy('sort');
                        }
                    ]);
                }
            ])
            ->whereNull('parent_id')
            ->orderBy('sort')
            ->get();

        return $this->success($menus->toArray());
    }

    public function userMenus()
    {
        $user = auth()->user();

        if ($user->hasRole('super-admin')) {
            $menus = AdminMenu::active()
                ->where('type', '!=', AdminMenu::TYPE_BUTTON)
                ->with([
                    'children' => function ($query) {
                        $query->active()
                            ->where('type', '!=', AdminMenu::TYPE_BUTTON)
                            ->orderBy('sort');
                    }
                ])
                ->whereNull('parent_id')
                ->orderBy('sort')
                ->get();
        } else {
            $roleIds = $user->roles()->where('status', 1)->pluck('hash_id');
            $menus   = AdminMenu::active()
                ->where('type', '!=', AdminMenu::TYPE_BUTTON)
                ->whereHas('roles', function ($query) use ($roleIds) {
                    $query->whereIn('hash_id', $roleIds);
                })
                ->with([
                    'children' => function ($query) use ($roleIds) {
                        $query->active()
                            ->where('type', '!=', AdminMenu::TYPE_BUTTON)
                            ->whereHas('roles', function ($query) use ($roleIds) {
                                $query->whereIn('hash_id', $roleIds);
                            })
                            ->orderBy('sort');
                    }
                ])
                ->whereNull('parent_id')
                ->orderBy('sort')
                ->get();
        }

        return $this->success($menus->toArray());
    }

    public function updateSort(Request $request)
    {
        $request->validate([
            'menus'           => 'required|array|max:200',
            'menus.*.hash_id' => 'required|string|max:20',
            'menus.*.sort'    => 'required|integer|min:0',
        ]);

        foreach ($request->menus as $menu) {
            AdminMenu::where('hash_id', $menu['hash_id'])->update(['sort' => $menu['sort']]);
        }

        return $this->success([], '菜单排序更新成功');
    }

    public function batchUpdateStatus(Request $request)
    {
        $request->validate([
            'ids'    => 'required|array',
            'status' => 'required|in:0,1'
        ]);

        AdminMenu::whereIn('hash_id', $request->ids)->update(['status' => $request->status]);

        return $this->success([], '状态更新成功');
    }
}
