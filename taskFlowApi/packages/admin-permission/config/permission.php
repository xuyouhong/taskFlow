<?php

return [
    /*
    |--------------------------------------------------------------------------
    | 权限配置
    |--------------------------------------------------------------------------
    */

    // 超级管理员角色标识
    'super_admin_role' => 'super-admin',

    // 认证配置
    'auth'             => [
        // API 前缀
        'api_prefix'   => 'admin',

        // 登录失败最大尝试次数
        'max_attempts' => 5,

        // 失败后锁定时间（分钟）
        'lockout_time' => 15,
    ],

    // 默认分页数量
    'pagination'       => [
        'per_page' => 15,
    ],

    // 日志配置
    'logs'             => [
        // 登录日志保留天数 (0为永久保留)
        'login_retention_days'     => 90,

        // 操作日志保留天数 (0为永久保留)
        'operation_retention_days' => 90,

        // 是否记录操作日志
        'enable_operation_log'     => true,

        // 排除记录的操作路径
        'except_operation_paths'   => [
            'logs*',
            'captcha*',
        ],
    ],

    // Hash ID 配置
    'hashids'          => [
        // 加密盐值，请在生产环境修改为复杂随机字符串
        'salt'       => env('ADMIN_PERMISSION_HASHIDS_SALT', 'admin-permission-rbac'),

        // Hash ID 最小长度
        'min_length' => 16,
    ],

    // 验证码配置
    'captcha'          => [
        // 驱动: math (GD图像) | svg (矢量图)
        'driver'         => 'math',

        // 图片宽度
        'width'          => 280,

        // 图片高度
        'height'         => 80,

        // 数学运算数字范围
        'max_number'     => 99,

        // 验证码过期时间(分钟)
        'expire'         => 5,

        // 字符集（仅 SVG 模式使用）
        'charset'        => '23456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz',

        // 字符长度（仅 SVG 模式使用）
        'length'         => 4,

        // 是否区分大小写
        'case_sensitive' => false,

        // TTF 字体文件路径（留空则自动搜索系统字体）
        'font_path'      => '',

        // 字体大小（仅 TTF 模式生效）
        'font_size'      => 32,
    ],

    // 菜单配置
    'menu'             => [
        // 最大层级深度
        'max_depth'    => 3,

        // 默认图标
        'default_icon' => 'el-icon-menu',
    ],

    // 用户配置
    'user'             => [
        // 默认头像
        'default_avatar'  => 'https://oss.eyesnews.cn/dev/upload/image/2025/05/15/dD0xNzQ3Mjk1NzMx.gif',

        // 允许上传的头像类型
        'avatar_mimes'    => 'jpg,jpeg,png,gif',

        // 头像最大大小(KB)
        'avatar_max_size' => 2048,
    ],

    // 上传配置
    'upload'           => [
        // 是否将图片转换为webp格式
        'convert_to_webp' => true,

        // webp转换质量 (0-100)
        'webp_quality'    => 80,
    ],
];
