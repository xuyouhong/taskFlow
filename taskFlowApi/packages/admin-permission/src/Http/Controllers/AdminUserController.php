<?php

namespace Admin\Permission\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Admin\Permission\Models\AdminUser;
use Admin\Permission\Models\AdminRole;
use Admin\Permission\Http\Requests\AdminUserRequest;

class AdminUserController extends AdminController
{
    public function index(Request $request)
    {
        $query = AdminUser::with('roles');

        if ($request->has('username') && $request->username) {
            $query->where('username', 'like', "%{$request->username}%");
        }

        if ($request->has('real_name') && $request->real_name) {
            $query->where('real_name', 'like', "%{$request->real_name}%");
        }

        if ($request->has('status') && $request->status !== null) {
            $query->where('status', $request->status);
        }

        $users = $query->orderBy('_seq', 'desc')->paginate($request->get('per_page', 15));

        return $this->paginate($users);
    }

    public function store(AdminUserRequest $request)
    {
        $data             = $request->validated();
        $data['password'] = Hash::make($data['password']);

        $user = AdminUser::create($data);

        if ($request->has('role_ids')) {
            $user->roles()->sync($request->role_ids);
        }

        return $this->success($user->toArray(), '用户创建成功');
    }

    public function show($hashId)
    {
        $user = AdminUser::findOrFail($hashId);
        $user->load('roles');
        return $this->success($user->toArray());
    }

    public function update(AdminUserRequest $request, $hashId)
    {
        $user = AdminUser::findOrFail($hashId);
        $data = $request->validated();

        if ($request->has('password') && $request->password) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        if ($request->has('role_ids')) {
            $user->roles()->sync($request->role_ids);
        }

        return $this->success($user->toArray(), '用户更新成功');
    }

    public function destroy($hashId)
    {
        $user = AdminUser::findOrFail($hashId);

        if ($user->hasRole('super-admin')) {
            return $this->error('超级管理员不能删除');
        }

        $user->delete();
        return $this->success([], '用户删除成功');
    }

    public function roles()
    {
        $roles = AdminRole::where('status', 1)->get();
        return $this->success($roles->toArray());
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'real_name' => 'nullable|string|max:50',
            'email'     => 'nullable|email|max:100|unique:admin_users,email,' . $user->hash_id . ',hash_id',
            'phone'     => 'nullable|string|max:20',
            'avatar'    => 'nullable|string',
        ]);

        $user->update($request->only(['real_name', 'email', 'phone', 'avatar']));

        return $this->success($user->toArray(), '个人信息更新成功');
    }

    public function changePassword(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'old_password' => 'required|string',
            'new_password' => 'required|string|min:6|max:20|confirmed',
        ]);

        if (!Hash::check($request->old_password, $user->password)) {
            return $this->error('原密码错误');
        }

        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        return $this->success([], '密码修改成功');
    }

    public function batchUpdateStatus(Request $request)
    {
        $request->validate([
            'ids'    => 'required|array',
            'status' => 'required|in:0,1'
        ]);

        AdminUser::whereIn('hash_id', $request->ids)
            ->whereDoesntHave('roles', function ($q) {
                $q->where('slug', 'super-admin');
            })
            ->update(['status' => $request->status]);

        return $this->success([], '状态更新成功');
    }
}
