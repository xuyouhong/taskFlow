<?php

namespace Admin\Permission\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $roleId = $this->route('role');

        return [
            'name'             => 'required|string|max:50',
            'slug'             => [
                'required',
                'string',
                'max:50',
                Rule::unique('admin_roles', 'slug')->ignore($roleId, 'hash_id') // 更新表名
            ],
            'description'      => 'nullable|string',
            'status'           => 'required|in:0,1',
            'sort'             => 'required|integer|min:0',
            'permission_ids'   => 'nullable|array',
            'permission_ids.*' => 'string|max:20',
            'menu_ids'         => 'nullable|array',
            'menu_ids.*'       => 'string|max:20',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'   => '角色名称不能为空。',
            'slug.required'   => '角色标识不能为空。',
            'slug.unique'     => '角色标识已被使用。',
            'status.required' => '状态不能为空。',
            'status.in'       => '状态值不正确。',
            'sort.required'   => '排序不能为空。',
            'sort.integer'    => '排序必须为整数。',
            'sort.min'        => '排序值不能小于0。',
        ];
    }

    public function attributes(): array
    {
        return [
            'name'           => '角色名称',
            'slug'           => '角色标识',
            'description'    => '角色描述',
            'status'         => '状态',
            'sort'           => '排序',
            'permission_ids' => '权限',
            'menu_ids'       => '菜单',
        ];
    }
}