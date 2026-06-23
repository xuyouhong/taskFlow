# Admin Permission RBAC

基于 Laravel 11 + Sanctum 的 RBAC 权限管理扩展包，提供完整的用户、角色、权限、菜单管理，内置验证码和操作日志。

## 功能特性

- 用户管理：创建、编辑、删除、批量启用/禁用
- 角色管理：多角色支持，灵活分配权限和菜单
- 权限管理：基于路由名称的细粒度权限控制
- 菜单管理：三级树形结构，支持目录 / 菜单 / 按钮类型
- 登录日志：自动记录登录信息（浏览器、操作系统、IP 归属地）
- 操作日志：自动记录增删改操作及响应内容
- 仪表盘：系统概览、图表统计、快速统计
- 通知通告：后台发布通知，用户端查看和已读标记
- 文件上传：支持图片自动转 WebP
- **Hash ID**：对外暴露混淆后的 Hash ID，保护数据安全
- **内置验证码**：纯 PHP 实现，支持数学运算（GD）和 SVG 两种模式

## 安装要求

- PHP >= 8.2
- Laravel >= 11
- Laravel Sanctum >= 4.0
- hashids/hashids >= 5.0
- (可选) intervention/image >= 3.11 — 图片处理

## 安装步骤

### 1. 配置 Composer 仓库

在项目根目录 `composer.json` 中添加本地路径仓库：

```json
{
  "repositories": [
    {
      "type": "path",
      "url": "packages/*"
    }
  ]
}
```

### 2. 通过 Composer 安装

```bash
composer require admin/permission
```

### 3. 发布资源

```bash
# 发布配置文件
php artisan vendor:publish --provider="Admin\Permission\PermissionServiceProvider" --tag="admin-permission-config"

# 发布数据库迁移
php artisan vendor:publish --provider="Admin\Permission\PermissionServiceProvider" --tag="admin-permission-migrations" --force

# 执行迁移
php artisan migrate

# 填充初始数据
php artisan db:seed --class=AdminPermissionSeeder
```

### 4. 配置 Sanctum

在 `.env` 中添加：

```env
SANCTUM_STATEFUL_DOMAINS=localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1
```

### 5. 配置缓存和会话驱动

本扩展包不包含 `cache` 和 `sessions` 数据表的迁移，因此需要将缓存和会话驱动设置为 `file`（或其他非数据库驱动）：

```env
CACHE_STORE=file
SESSION_DRIVER=file
```

> 如果需要使用数据库驱动，请自行创建对应的 `cache` 和 `sessions` 表（Laravel 内置迁移：`php artisan session:table` /
`php artisan cache:table`）。

### 6. 配置 Hash ID（建议）

生产环境建议修改 Hash ID 的加密盐值：

```env
ADMIN_PERMISSION_HASHIDS_SALT=your_complex_random_string_here
```

## 默认账号

| 角色    | 用户名   | 密码      |
|-------|-------|---------|
| 超级管理员 | super | gzrbbks |
| 管理员   | admin | 123456  |
| 普通用户  | user  | 123456  |

## API 接口

> 注意：所有接口中返回的 `id` 字段已替换为 `hash_id`（Hash ID 字符串），前端请求时也应传递 `hash_id`。

### 认证

| 方法   | 路径             | 说明       |
|------|----------------|----------|
| POST | /admin/login   | 登录（需验证码） |
| GET  | /admin/captcha | 获取验证码    |
| GET  | /admin/user    | 获取当前用户信息 |
| POST | /admin/logout  | 退出登录     |
| POST | /admin/refresh | 刷新令牌     |

### 用户管理

| 方法     | 路径                        | 说明     |
|--------|---------------------------|--------|
| GET    | /admin/users              | 用户列表   |
| POST   | /admin/users              | 创建用户   |
| GET    | /admin/users/{hash_id}    | 用户详情   |
| PUT    | /admin/users/{hash_id}    | 更新用户   |
| DELETE | /admin/users/{hash_id}    | 删除用户   |
| POST   | /admin/users/batch-status | 批量更新状态 |

### 角色管理

| 方法     | 路径                                        | 说明     |
|--------|-------------------------------------------|--------|
| GET    | /admin/roles                              | 角色列表   |
| POST   | /admin/roles                              | 创建角色   |
| GET    | /admin/roles/{hash_id}                    | 角色详情   |
| PUT    | /admin/roles/{hash_id}                    | 更新角色   |
| DELETE | /admin/roles/{hash_id}                    | 删除角色   |
| POST   | /admin/roles/batch-status                 | 批量更新状态 |
| POST   | /admin/roles/assign-permissions/{hash_id} | 分配权限   |
| POST   | /admin/roles/assign-menus/{hash_id}       | 分配菜单   |

### 权限管理

| 方法     | 路径                              | 说明       |
|--------|---------------------------------|----------|
| GET    | /admin/permissions              | 权限列表     |
| POST   | /admin/permissions              | 创建权限     |
| GET    | /admin/permissions/{hash_id}    | 权限详情     |
| PUT    | /admin/permissions/{hash_id}    | 更新权限     |
| DELETE | /admin/permissions/{hash_id}    | 删除权限     |
| POST   | /admin/permissions/batch-status | 批量更新状态   |
| POST   | /admin/permissions/sync-routes  | 同步路由到权限表 |

### 菜单管理

| 方法     | 路径                        | 说明       |
|--------|---------------------------|----------|
| GET    | /admin/menus              | 菜单列表（树形） |
| POST   | /admin/menus              | 创建菜单     |
| GET    | /admin/menus/{hash_id}    | 菜单详情     |
| PUT    | /admin/menus/{hash_id}    | 更新菜单     |
| DELETE | /admin/menus/{hash_id}    | 删除菜单     |
| GET    | /admin/menus-tree         | 激活的菜单树   |
| GET    | /admin/user-menus         | 当前用户的菜单  |
| PUT    | /admin/menus/sort         | 更新排序     |
| POST   | /admin/menus/batch-status | 批量更新状态   |

### 仪表盘

| 方法  | 路径                             | 说明    |
|-----|--------------------------------|-------|
| GET | /admin/dashboard               | 仪表盘首页 |
| GET | /admin/dashboard/chart         | 图表数据  |
| GET | /admin/dashboard/quick-stats   | 快速统计  |
| GET | /admin/dashboard/recent-logins | 最近登录  |

### 个人中心

| 方法  | 路径              | 说明     |
|-----|-----------------|--------|
| PUT | /admin/profile  | 更新个人资料 |
| PUT | /admin/password | 修改密码   |

### 登录日志

| 方法     | 路径                              | 说明   |
|--------|---------------------------------|------|
| GET    | /admin/login-logs               | 日志列表 |
| GET    | /admin/login-logs/{hash_id}     | 日志详情 |
| DELETE | /admin/login-logs/{hash_id}     | 删除日志 |
| POST   | /admin/login-logs/batch-destroy | 批量删除 |
| GET    | /admin/login-logs/statistics    | 统计数据 |

### 操作日志

| 方法     | 路径                                  | 说明    |
|--------|-------------------------------------|-------|
| GET    | /admin/operation-logs               | 日志列表  |
| GET    | /admin/operation-logs/{hash_id}     | 日志详情  |
| DELETE | /admin/operation-logs/{hash_id}     | 删除日志  |
| POST   | /admin/operation-logs/batch-destroy | 批量删除  |
| POST   | /admin/operation-logs/clean         | 清理旧日志 |
| GET    | /admin/operation-logs/statistics    | 统计数据  |

### 通知管理（管理员）

| 方法     | 路径                                           | 说明   |
|--------|----------------------------------------------|------|
| GET    | /admin/admin-notifications                   | 通知列表 |
| POST   | /admin/admin-notifications                   | 创建通知 |
| GET    | /admin/admin-notifications/{hash_id}         | 通知详情 |
| PUT    | /admin/admin-notifications/{hash_id}         | 更新通知 |
| DELETE | /admin/admin-notifications/{hash_id}         | 删除通知 |
| POST   | /admin/admin-notifications/{hash_id}/publish | 发布通知 |
| POST   | /admin/admin-notifications/{hash_id}/revoke  | 撤销通知 |

### 用户通知（普通用户）

| 方法     | 路径                                  | 说明     |
|--------|-------------------------------------|--------|
| GET    | /admin/notifications                | 我的通知列表 |
| GET    | /admin/notifications/unread-count   | 未读数量   |
| POST   | /admin/notifications/{hash_id}/read | 标记已读   |
| POST   | /admin/notifications/read-all       | 全部已读   |
| GET    | /admin/notifications/{hash_id}      | 通知详情   |
| DELETE | /admin/notifications/{hash_id}      | 删除通知   |
| POST   | /admin/notifications/batch-destroy  | 批量删除   |

### 文件上传

| 方法   | 路径                  | 说明    |
|------|---------------------|-------|
| POST | /admin/upload       | 单文件上传 |
| POST | /admin/upload/batch | 批量上传  |

## 配置参考

```php
// config/permission.php
return [
    // 超级管理员角色标识
    'super_admin_role' => 'super-admin',

    // 认证配置
    'auth' => [
        'api_prefix'   => 'admin',
        'max_attempts' => 5,
        'lockout_time' => 15, // 分钟
    ],

    // 分页
    'pagination' => ['per_page' => 15],

    // 日志
    'logs' => [
        'login_retention_days'     => 90,
        'operation_retention_days' => 90,
        'enable_operation_log'     => true,
        'except_operation_paths'   => ['logs*', 'captcha*'],
    ],

    // Hash ID 混淆
    'hashids' => [
        'salt'       => env('ADMIN_PERMISSION_HASHIDS_SALT', 'admin-permission-rbac'),
        'min_length' => 8,
    ],

    // 验证码
    'captcha' => [
        'driver'         => 'math',  // math | svg
        'width'          => 280,
        'height'         => 80,
        'max_number'     => 99,
        'expire'         => 5,       // 分钟
        'charset'        => '23456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz',
        'length'         => 4,
        'case_sensitive' => false,
    ],

    // 菜单
    'menu' => [
        'max_depth'    => 3,
        'default_icon' => 'el-icon-menu',
    ],

    // 用户
    'user' => [
        'default_avatar'  => 'https://...',
        'avatar_mimes'    => 'jpg,jpeg,png,gif',
        'avatar_max_size' => 2048,
    ],

    // 上传
    'upload' => [
        'convert_to_webp' => true,
        'webp_quality'    => 80,
    ],
];
```

## 目录结构

```
packages/admin-permission/
├── config/permission.php              # 配置文件
├── database/
│   ├── migrations/                    # 数据库迁移
│   └── Seeders/                       # 数据填充
├── src/
│   ├── Traits/
│   │   ├── HasHashId.php              # 模型 Hash ID Trait
│   │   └── HasHashIdController.php    # 控制器 Hash ID 辅助
│   ├── Http/
│   │   ├── Controllers/               # 控制器
│   │   ├── Middleware/                # 中间件
│   │   └── Requests/                  # 表单请求验证
│   ├── Models/                        # 数据模型
│   ├── Services/
│   │   └── CaptchaService.php         # 验证码服务
│   └── PermissionServiceProvider.php  # 服务提供者
├── routes/api.php                     # API 路由
├── composer.json
└── README.md
```

## 权限模型

- 用户 (User) → 多对多 → 角色 (Role) → 多对多 → 权限 (Permission)
- 用户 (User) → 多对多 → 角色 (Role) → 多对多 → 菜单 (Menu)
- 超级管理员角色 (`super-admin`) 拥有所有权限，跳过权限验证中间件
- 权限基于 Laravel 路由名称 (`route name`) 进行验证

## 中间件

| 别名            | 类                      | 说明           |
|---------------|------------------------|--------------|
| permission    | PermissionMiddleware   | 基于路由名称验证用户权限 |
| operation.log | OperationLogMiddleware | 自动记录操作日志     |

在路由中使用：

```php
Route::middleware(['auth:sanctum', 'permission', 'operation.log'])->group(function () {
    // 需要权限验证并记录操作日志的路由
});
```

## 升级日志

### v2.0.1

- 修复 Hash ID 冲突：不同模型使用独立 salt（基于表名），避免相同 ID 产生相同 hash
- API 响应自动清除 BelongsToMany 的 pivot 数据，防止内部 ID 泄露
- 修复 AdminNotification 模型：移除不匹配的 SoftDeletes（表无 deleted_at 字段）
- 控制器 decode 方法统一委托给 Model，确保编码/解码一致性
- README 补充 `CACHE_STORE=file` 和 `SESSION_DRIVER=file` 配置说明

### v2.0.0

- 所有 API 响应使用 Hash ID 替代自增 ID
- 重写验证码服务（移除 mews/captcha 依赖，支持 math + svg 双模式）
- 移除无用的 users / cache / jobs 迁移文件
- 删除 vendor 目录（由根项目 composer 统一管理）
- 优化控制器代码，移除冗余检查
- 新增 `HasHashId` 和 `HasHashIdController` Trait

### v1.0.0

- 初始版本发布

## 许可证

MIT License

## 问题反馈

- Email: xuyouhong_@hotmail.com
