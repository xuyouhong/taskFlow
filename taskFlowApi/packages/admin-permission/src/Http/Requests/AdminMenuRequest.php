<?php

namespace Admin\Permission\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdminMenuRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'parent_id'   => 'nullable|string|max:20',
            'name'        => 'required|string|max:50',
            'icon'        => 'nullable|string|max:50',
            'path'        => 'nullable|string|max:255',
            'component'   => 'nullable|string|max:255',
            'sort'        => 'required|integer|min:0',
            'type'        => 'required|in:1,2,3',
            'status'      => 'required|in:0,1',
            'is_link'     => 'required|in:0,1',
            'keep_alive'  => 'required|in:0,1',
            'description' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'parent_id.max'       => '父级菜单ID长度不能超过20个字符。',
            'name.required'       => '菜单名称不能为空。',
            'name.max'            => '菜单名称不能超过50个字符。',
            'icon.max'            => '图标不能超过50个字符。',
            'path.max'            => '路径不能超过255个字符。',
            'component.max'       => '组件不能超过255个字符。',
            'sort.required'       => '排序不能为空。',
            'sort.integer'        => '排序必须为整数。',
            'sort.min'            => '排序值不能小于0。',
            'type.required'       => '菜单类型不能为空。',
            'type.in'             => '菜单类型值不正确。',
            'status.required'     => '状态不能为空。',
            'status.in'           => '状态值不正确。',
            'is_link.required'    => '外链标识不能为空。',
            'is_link.in'          => '外链标识值不正确。',
            'keep_alive.required' => '缓存标识不能为空。',
            'keep_alive.in'       => '缓存标识值不正确。',
        ];
    }

    public function attributes(): array
    {
        return [
            'parent_id'   => '父级菜单',
            'name'        => '菜单名称',
            'icon'        => '图标',
            'path'        => '路径',
            'component'   => '组件',
            'sort'        => '排序',
            'type'        => '菜单类型',
            'status'      => '状态',
            'is_link'     => '外链标识',
            'keep_alive'  => '缓存标识',
            'description' => '描述',
        ];
    }
}