<?php

namespace Admin\Permission\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdminNotificationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'title'         => 'required|string|max:255',
            'content'       => 'required|string',
            'type'          => [
                'required',
                'integer',
                Rule::in([1, 2]) // 1-通知，2-通告
            ],
            'priority'      => [
                'required',
                'integer',
                Rule::in([1, 2, 3]) // 1-普通，2-重要，3-紧急
            ],
            'target_type'   => [
                'required',
                'integer',
                Rule::in([1, 2, 3]) // 1-所有用户，2-指定角色，3-指定用户
            ],
            'target_values' => 'nullable|array',
            'publish_time'  => 'nullable|date',
            'expire_time'   => 'nullable|date|after:publish_time',
            'status'        => [
                'required',
                'integer',
                Rule::in([1, 2, 3]) // 1-草稿，2-已发布，3-已撤销
            ]
        ];

        // 根据不同的请求方法，调整验证规则
        switch ($this->method()) {
            case 'POST':
                // 创建请求的额外规则
                break;

            case 'PUT':
            case 'PATCH':
                // 更新请求的额外规则
                break;

            case 'DELETE':
                // 删除请求的规则
                $rules = [];
                break;

            default:
                break;
        }

        return $rules;
    }

    /**
     * Get the validation messages that apply to the request.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'title.required'       => '标题不能为空',
            'title.string'         => '标题必须是字符串',
            'title.max'            => '标题最多255个字符',
            'content.required'     => '内容不能为空',
            'content.string'       => '内容必须是字符串',
            'type.required'        => '类型不能为空',
            'type.integer'         => '类型必须是整数',
            'type.in'              => '类型必须是1-通知或2-通告',
            'priority.required'    => '优先级不能为空',
            'priority.integer'     => '优先级必须是整数',
            'priority.in'          => '优先级必须是1-普通、2-重要或3-紧急',
            'target_type.required' => '接收对象类型不能为空',
            'target_type.integer'  => '接收对象类型必须是整数',
            'target_type.in'       => '接收对象类型必须是1-所有用户、2-指定角色或3-指定用户',
            'target_values.array'  => '接收对象值必须是数组格式',
            'publish_time.date'    => '发布时间必须是有效的日期格式',
            'expire_time.date'     => '过期时间必须是有效的日期格式',
            'expire_time.after'    => '过期时间必须晚于发布时间',
            'status.required'      => '状态不能为空',
            'status.integer'       => '状态必须是整数',
            'status.in'            => '状态必须是1-草稿、2-已发布或3-已撤销'
        ];
    }
}
