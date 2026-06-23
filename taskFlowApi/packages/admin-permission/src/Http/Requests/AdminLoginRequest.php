<?php

namespace Admin\Permission\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminLoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'username'     => 'required|string|max:50',
            'password'     => 'required|string|min:6|max:20',
            'captcha_key'  => 'required|string',
            'captcha_code' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'username.required'     => '用户名不能为空。',
            'password.required'     => '密码不能为空。',
            'password.min'          => '密码长度不能少于6位。',
            'password.max'          => '密码长度不能超过20位。',
            'captcha_key.required'  => '验证码KEY不能为空。',
            'captcha_code.required' => '验证码不能为空。',
        ];
    }

    public function attributes(): array
    {
        return [
            'username'     => '用户名',
            'password'     => '密码',
            'captcha_key'  => '验证码KEY',
            'captcha_code' => '验证码',
        ];
    }
}
