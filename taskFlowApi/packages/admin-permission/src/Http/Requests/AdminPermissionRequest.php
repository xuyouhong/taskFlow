<?php

namespace Admin\Permission\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminPermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $permissionId = $this->route('permission');

        return [
            'name'        => 'required|string|max:50',
            'slug'        => [
                'required',
                'string',
                'max:100',
                Rule::unique('admin_permissions', 'slug')->ignore($permissionId, 'hash_id') // 更新表名
            ],
            'http_method' => 'nullable|string',
            'http_path'   => 'nullable|string',
            'description' => 'nullable|string',
            'status'      => 'required|in:0,1',
            'sort'        => 'required|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'   => '权限名称不能为空。',
            'slug.required'   => '权限标识不能为空。',
            'slug.unique'     => '权限标识已被使用。',
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
            'name'        => '权限名称',
            'slug'        => '权限标识',
            'http_method' => 'HTTP方法',
            'http_path'   => 'HTTP路径',
            'description' => '权限描述',
            'status'      => '状态',
            'sort'        => '排序',
        ];
    }
}