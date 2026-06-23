<?php

namespace Admin\Permission\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user');

        $rules = [
            'username'   => [
                'required',
                'string',
                'max:50',
                Rule::unique('admin_users', 'username')->ignore($userId, 'hash_id') // 更新表名
            ],
            'email'      => [
                'nullable',
                'email',
                'max:100',
                Rule::unique('admin_users', 'email')->ignore($userId, 'hash_id') // 更新表名
            ],
            'real_name'  => 'nullable|string|max:50',
            'avatar'     => 'nullable|string|ends_with:.jpeg,.jpg,.png,.gif,.webp',
            'phone'      => 'nullable|string|max:20',
            'status'     => 'required|in:0,1',
            'role_ids'   => 'nullable|array',
            'role_ids.*' => 'string|max:20',
        ];

        if ($this->isMethod('POST')) {
            $rules['password'] = 'required|string|min:6|max:20';
        } else {
            $rules['password'] = 'nullable|string|min:6|max:20';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'username.required' => '用户名不能为空。',
            'username.unique'   => '用户名已被使用。',
            'email.email'       => '邮箱格式不正确。',
            'email.unique'      => '邮箱已被使用。',
            'password.required' => '密码不能为空。',
            'password.min'      => '密码长度不能少于6位。',
            'password.max'      => '密码长度不能超过20位。',
            'avatar.ends_with'  => '头像文件路径格式不正确，必须是jpeg、png、jpg、gif或webp格式的图片文件。',
            'status.required'   => '状态不能为空。',
            'status.in'         => '状态值不正确。',
        ];
    }

    public function attributes(): array
    {
        return [
            'username'  => '用户名',
            'email'     => '邮箱',
            'password'  => '密码',
            'real_name' => '真实姓名',
            'phone'     => '手机号',
            'avatar'    => '头像',
            'status'    => '状态',
            'role_ids'  => '角色',
        ];
    }
}