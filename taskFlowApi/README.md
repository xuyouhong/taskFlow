# 任务流调度平台 - 后端

基于 **Laravel 12 + PHP 8.2** 构建的定时任务调度管理系统后端。

## 技术栈

| 类别 | 技术 | 版本 |
|------|------|------|
| 框架 | Laravel | ^12.0 |
| 语言 | PHP | ^8.2 |
| 数据库 | MySQL / SQLite | - |
| 认证 | Laravel Sanctum | - |
| 权限 | admin/permission | dev-stable |
| 队列 | Laravel Queue | database |
| Cron解析 | dragonmantank/cron-expression | ^3.6 |

---

## 快速开始

### 环境要求

- PHP >= 8.2
- Composer
- MySQL 5.7+ 或 SQLite
- 可选：Redis（队列缓存加速）

### 安装依赖

```bash
cd taskFlowApi
composer install
```

### 环境配置

```bash
# 复制环境配置文件
cp .env.example .env

# 生成应用密钥
php artisan key:generate
```

编辑 `.env` 文件，配置数据库连接：

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=taskflow
DB_USERNAME=root
DB_PASSWORD=your_password

# Sanctum 配置
SANCTUM_STATEFUL_DOMAINS=localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1

# 缓存和会话（推荐 file 驱动）
CACHE_STORE=file
SESSION_DRIVER=file

# Hash ID 加密盐值（生产环境请修改）
ADMIN_PERMISSION_HASHIDS_SALT=your_complex_random_string_here
```

### 数据库迁移

```bash
# 执行数据库迁移
php artisan migrate --force

# 填充初始数据（RBAC 权限数据）
php artisan db:seed --class=AdminPermissionSeeder
```

### 初始化管理员账号

```bash
php artisan tinker
```

```php
$user = AdminUser::create([
    'username' => 'admin',
    'password' => bcrypt('admin123'),
    'name' => '超级管理员',
    'is_super_admin' => 1,
    'status' => 'enabled',
]);
```

### 开发运行

```bash
# 启动开发服务器（默认 8000 端口）
php artisan serve --port=8000

# 前端开发服务器（默认 3000 端口）
cd ../taskFlowFrontend && npm run dev
```

API 基础地址：`http://127.0.0.1:8000`
前端基础地址：`http://localhost:3000`

---

## 启动任务调度

定时任务需要启动调度器和队列工作进程。

### 1. 启动队列工作进程（必须）

任务通过队列异步执行：

```bash
php artisan queue:work --tries=3
```

生产环境建议使用 Supervisor 管理队列进程。

### 2. 启动调度器

**方式一：分钟级调度（推荐生产环境）**

配置系统 Cron 任务（每分钟触发一次）：

```bash
crontab -e
```

添加：

```
* * * * * cd /path/to/taskFlowApi && php artisan schedule:run >> /dev/null 2>&1
```

**方式二：秒级调度（守护进程）**

如果需要秒级精度（如每30秒执行），使用守护进程模式：

```bash
# 默认1秒扫描间隔
php artisan scheduler:daemon

# 自定义扫描间隔（2秒）
php artisan scheduler:daemon --sleep=2
```

**手动测试调度：**

```bash
php artisan scheduler:run
```

---

## 项目结构

```
taskFlowApi/
├── app/
│   ├── Console/
│   │   └── Commands/
│   │       ├── SchedulerRun.php       # 单次调度命令
│   │       └── SchedulerDaemon.php     # 守护进程调度命令
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── Api/
│   │   │       └── V1/
│   │   │           ├── BaseController.php
│   │   │           ├── ProjectController.php
│   │   │           ├── TaskController.php
│   │   │           ├── TaskLogController.php
│   │   │           ├── NodeController.php
│   │   │           ├── AgentController.php
│   │   │           └── NotificationChannelController.php
│   │   └── Middleware/
│   ├── Jobs/
│   │   └── ExecuteTask.php            # 任务执行Job
│   ├── Models/
│   │   ├── BaseModel.php
│   │   ├── Task.php
│   │   ├── TaskLog.php
│   │   ├── TaskLogDetail.php
│   │   ├── Project.php
│   │   ├── Node.php
│   │   ├── NotificationChannel.php
│   │   ├── TaskNotification.php
│   │   └── AdminUser.php              # 别名自 admin-permission 包
│   ├── Providers/
│   │   └── AppServiceProvider.php     # 加载调度模块路由
│   └── Traits/
│       └── HasHashId.php              # HashID特性
├── config/
├── database/
│   ├── migrations/                    # 数据库迁移
│   └── seeders/                       # 数据填充
├── packages/
│   └── admin-permission/              # 权限管理包
│       ├── src/
│       │   ├── Http/Controllers/     # 控制器（用户、角色、权限、菜单、日志等）
│       │   ├── Middleware/           # 中间件（permission、operation.log）
│       │   └── Models/               # 模型
│       ├── config/permission.php
│       ├── database/
│       │   ├── migrations/
│       │   └── Seeders/
│       └── routes/api.php
├── routes/
│   ├── scheduler.php                  # 任务调度API路由（无/api前缀）
│   ├── api.php                        # 系统管理API路由（/admin前缀）
│   ├── web.php
│   └── console.php                    # 命令调度配置
├── storage/
├── .env.example
├── artisan
└── composer.json
```

---

## API 接口说明

### 接口分组

| 分组 | 路径前缀 | 说明 |
|------|---------|------|
| 系统管理 | `/admin` | 用户、角色、权限、菜单、操作日志等 |
| 任务调度 | `/v1` | 项目、任务、执行日志、节点、通知渠道 |

### 认证方式

使用 Bearer Token 认证，登录后获取 Token：

```
POST /admin/login
Content-Type: application/json

{
    "captcha_key": "xxx",
    "captcha_code": "1234",
    "username": "admin",
    "password": "123456"
}
```

后续请求在 Header 中携带：

```
Authorization: Bearer {token}
```

---

## 系统管理 API（/admin）

### 1. 认证

| 方法 | 路径 | 说明 |
|------|------|------|
| POST | `/admin/login` | 登录（需验证码） |
| GET | `/admin/captcha` | 获取验证码 |
| GET | `/admin/user` | 获取当前用户信息 |
| POST | `/admin/logout` | 退出登录 |
| POST | `/admin/refresh` | 刷新令牌 |

### 2. 用户管理

| 方法 | 路径 | 说明 | 权限标识 |
|------|------|------|----------|
| GET | `/admin/users` | 用户列表 | |
| POST | `/admin/users` | 创建用户 | |
| GET | `/admin/users/{hashId}` | 用户详情 | |
| PUT | `/admin/users/{hashId}` | 更新用户 | |
| DELETE | `/admin/users/{hashId}` | 删除用户 | |

### 3. 角色管理

| 方法 | 路径 | 说明 | 权限标识 |
|------|------|------|----------|
| GET | `/admin/roles` | 角色列表 | |
| POST | `/admin/roles` | 创建角色 | |
| GET | `/admin/roles/{hashId}` | 角色详情 | |
| PUT | `/admin/roles/{hashId}` | 更新角色 | |
| DELETE | `/admin/roles/{hashId}` | 删除角色 | |
| POST | `/admin/roles/assign-permissions/{hashId}` | 分配权限 | |
| POST | `/admin/roles/assign-menus/{hashId}` | 分配菜单 | |

### 4. 菜单管理

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/admin/menus` | 菜单列表（树形） |
| POST | `/admin/menus` | 创建菜单 |
| GET | `/admin/menus/{hashId}` | 菜单详情 |
| PUT | `/admin/menus/{hashId}` | 更新菜单 |
| DELETE | `/admin/menus/{hashId}` | 删除菜单 |
| GET | `/admin/user-menus` | 当前用户的菜单 |

### 5. 操作日志

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/admin/operation-logs` | 日志列表 |
| GET | `/admin/operation-logs/{hashId}` | 日志详情 |
| DELETE | `/admin/operation-logs/{hashId}` | 删除日志 |
| POST | `/admin/operation-logs/batch-destroy` | 批量删除 |
| POST | `/admin/operation-logs/clean` | 清理旧日志 |

### 6. 登录日志

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/admin/login-logs` | 日志列表 |
| GET | `/admin/login-logs/{hashId}` | 日志详情 |
| DELETE | `/admin/login-logs/{hashId}` | 删除日志 |

### 7. 仪表盘

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | `/admin/dashboard` | 仪表盘首页 |
| GET | `/admin/dashboard/chart` | 图表数据 |

---

## 任务调度 API（/v1）

### 1. 项目管理

| 方法 | 路径 | 说明 | 权限标识 |
|------|------|------|----------|
| GET | `/v1/projects` | 项目列表 | `projects.index` |
| POST | `/v1/projects` | 创建项目 | `projects.store` |
| GET | `/v1/projects/{hashId}` | 项目详情 | `projects.show` |
| PUT | `/v1/projects/{hashId}` | 更新项目 | `projects.update` |
| DELETE | `/v1/projects/{hashId}` | 删除项目 | `projects.destroy` |
| GET | `/v1/projects/{hashId}/members` | 成员列表 | `projects.members` |
| POST | `/v1/projects/{hashId}/members` | 添加成员 | `projects.members` |
| DELETE | `/v1/projects/{hashId}/members/{userId}` | 移除成员 | `projects.members` |

---

### 2. 任务管理

| 方法 | 路径 | 说明 | 权限标识 |
|------|------|------|----------|
| GET | `/v1/tasks` | 任务列表 | `tasks.index` |
| POST | `/v1/tasks` | 创建任务 | `tasks.store` |
| GET | `/v1/tasks/{hashId}` | 任务详情 | `tasks.show` |
| PUT | `/v1/tasks/{hashId}` | 更新任务 | `tasks.update` |
| DELETE | `/v1/tasks/{hashId}` | 删除任务 | `tasks.destroy` |
| POST | `/v1/tasks/{hashId}/trigger` | 手动触发 | `tasks.trigger` |
| POST | `/v1/tasks/{hashId}/pause` | 暂停任务 | `tasks.pause` |
| POST | `/v1/tasks/{hashId}/resume` | 恢复任务 | `tasks.resume` |
| GET | `/v1/tasks/{hashId}/logs` | 任务执行日志 | `tasks.logs` |

#### 任务创建请求示例

```json
{
    "project_id": "QmKQ9KXq",
    "name": "数据同步任务",
    "description": "每小时同步一次数据",
    "cron_expression": "*/5 * * * *",
    "timezone": "Asia/Shanghai",
    "executor_type": "http",
    "executor_config": {
        "url": "https://api.example.com/sync",
        "method": "POST",
        "headers": {
            "Content-Type": "application/json"
        },
        "payload": {
            "type": "full"
        }
    },
    "retry_times": 3,
    "retry_interval": 60,
    "timeout": 300,
    "concurrency_strategy": "forbid",
    "misfire_strategy": "skip",
    "priority": 0,
    "status": "enabled"
}
```

#### 执行器类型

| 类型 | 说明 | 配置参数 |
|------|------|----------|
| `http` | HTTP请求 | `url`, `method`, `headers`, `payload` |
| `shell` | Shell命令 | `command`, `node_id` |
| `job` | Laravel Job | `job_class`, `params` |
| `mq` | 消息队列 | `topic`, `payload` |

#### 并发策略

| 策略 | 说明 |
|------|------|
| `forbid` | 禁止并发，已有实例运行时跳过 |
| `allow` | 允许并发执行 |
| `replace` | 终止前一个，启动新的 |

#### Cron 表达式

支持 **5位**（分钟级）和 **6位**（秒级）两种格式：

##### 5位格式：分 时 日 月 周

| 表达式 | 说明 | 示例 |
|--------|------|------|
| `* * * * *` | 每分钟 | 每小时 |
| `*/5 * * * *` | 每5分钟 | 每5分钟 |
| `*/15 * * * *` | 每15分钟 | 每15分钟 |
| `*/30 * * * *` | 每30分钟 | 每30分钟 |
| `0 * * * *` | 每小时整点 | 每小时 |
| `0 */2 * * *` | 每2小时 | 每2小时 |
| `0 */6 * * *` | 每6小时 | 0点、6点、12点、18点 |
| `0 */12 * * *` | 每12小时 | 0点、12点 |
| `0 0 * * *` | 每天午夜 | 每天0点 |
| `0 1 * * *` | 每天凌晨1点 | 每天1点 |
| `0 2 * * *` | 每天凌晨2点 | 每天2点 |
| `0 3 * * *` | 每天凌晨3点 | 每天3点 |
| `0 9 * * *` | 每天上午9点 | 每天9点 |
| `0 10 * * *` | 每天上午10点 | 每天10点 |
| `0 12 * * *` | 每天中午12点 | 每天12点 |
| `0 18 * * *` | 每天下午6点 | 每天18点 |
| `0 0 * * 0` | 每周日午夜 | 每周日0点 |
| `0 0 * * 1` | 每周一午夜 | 每周一0点 |
| `0 9 * * 1-5` | 工作日上午9点 | 周一到周五9点 |
| `0 9 * * 1,3,5` | 周一、三、五上午9点 | 每周一三五9点 |
| `0 10 * * 1-5` | 工作日上午10点 | 周一到周五10点 |
| `0 18 * * 1-5` | 工作日下午6点 | 周一到周五18点 |
| `0 0 1 * *` | 每月1日午夜 | 每月1日0点 |
| `0 0 15 * *` | 每月15日午夜 | 每月15日0点 |
| `0 0 1,15 * *` | 每月1日和15日午夜 | 每月1号和15号0点 |
| `0 0 1 * 0` | 每月第一个周日 | 每月第一个周日0点 |
| `0 0 * * 0` | 每周日 | 每周日0点 |
| `0 0 1,11,21 * *` | 每月1日、11日、21日午夜 | 每月1、11、21号0点 |

##### 6位格式：秒 分 时 日 月 周

| 表达式 | 说明 | 示例 |
|--------|------|------|
| `*/30 * * * * *` | 每30秒 | 每30秒 |
| `*/10 * * * * *` | 每10秒 | 每10秒 |
| `0 * * * * *` | 每分钟整点 | 每分钟 |
| `0 */5 * * * *` | 每5分钟 | 每5分钟 |
| `0 */15 * * * *` | 每15分钟 | 每15分钟 |
| `0 */30 * * * *` | 每30分钟 | 每30分钟 |
| `0 0 * * * *` | 每小时整点 | 每小时 |
| `30 * * * * *` | 每小时的第30秒 | 每小时30秒 |
| `0 30 9 * * *` | 每天上午9:00:30 | 每天9点30分30秒 |
| `0 */10 * * * *` | 每10分钟 | 每10分钟 |
| `0 0 0 * * *` | 每天午夜 | 每天0点 |
| `30 0 0 * * *` | 每天午夜0:00:30 | 每天0点0分30秒 |

---

### 3. 执行日志

| 方法 | 路径 | 说明 | 权限标识 |
|------|------|------|----------|
| GET | `/v1/logs` | 日志列表 | `logs.index` |
| GET | `/v1/logs/{hashId}` | 日志详情 | `logs.show` |
| GET | `/v1/logs/archive` | 归档日志 | `logs.archive` |

#### 日志状态

| 状态 | 说明 |
|------|------|
| `running` | 执行中 |
| `success` | 执行成功 |
| `failed` | 执行失败 |
| `timeout` | 执行超时 |
| `cancelled` | 已取消 |

#### 触发类型

| 类型 | 说明 |
|------|------|
| `schedule` | 定时触发 |
| `manual` | 手动触发 |
| `retry` | 重试触发 |

---

### 4. 节点管理

| 方法 | 路径 | 说明 | 权限标识 |
|------|------|------|----------|
| GET | `/v1/nodes` | 节点列表 | `nodes.index` |
| POST | `/v1/nodes` | 创建节点 | `nodes.store` |
| GET | `/v1/nodes/{hashId}` | 节点详情 | `nodes.show` |
| PUT | `/v1/nodes/{hashId}` | 更新节点 | `nodes.update` |
| DELETE | `/v1/nodes/{hashId}` | 删除节点 | `nodes.destroy` |

---

### 5. 通知渠道

| 方法 | 路径 | 说明 | 权限标识 |
|------|------|------|----------|
| GET | `/v1/notification-channels` | 渠道列表 | `notification-channels.index` |
| POST | `/v1/notification-channels` | 创建渠道 | `notification-channels.store` |
| GET | `/v1/notification-channels/{hashId}` | 渠道详情 | `notification-channels.show` |
| PUT | `/v1/notification-channels/{hashId}` | 更新渠道 | `notification-channels.update` |
| DELETE | `/v1/notification-channels/{hashId}` | 删除渠道 | `notification-channels.destroy` |

#### 渠道类型

| 类型 | 说明 |
|------|------|
| `email` | 邮件 |
| `webhook` | HTTP Webhook |
| `dingtalk` | 钉钉机器人 |
| `wework` | 企业微信机器人 |
| `feishu` | 飞书机器人 |

---

### 6. Agent 接口（Token 验证）

无需 Sanctum 认证，使用 Agent Token 验证：

| 方法 | 路径 | 说明 |
|------|------|------|
| POST | `/v1/agents/register` | Agent 注册 |
| POST | `/v1/agents/heartbeat` | Agent 心跳 |
| POST | `/v1/agents/callback` | 执行结果回调 |

---

## 统一响应格式

### 成功响应

```json
{
    "status_code": 200,
    "message": "success",
    "data": {}
}
```

### 分页响应

```json
{
    "status_code": 200,
    "message": "success",
    "data": {
        "current_page": 1,
        "data": [],
        "first_page_url": "...",
        "from": 1,
        "last_page": 10,
        "last_page_url": "...",
        "next_page_url": "...",
        "per_page": 15,
        "prev_page_url": "...",
        "to": 15,
        "total": 150
    }
}
```

### 错误响应

```json
{
    "status_code": 400,
    "message": "错误信息",
    "data": null
}
```

---

## 数据库表结构

### 系统管理表（admin-permission 包）

| 表名 | 说明 |
|------|------|
| `admin_users` | 管理员用户表 |
| `admin_roles` | 角色表 |
| `admin_permissions` | 权限表 |
| `admin_menus` | 菜单表 |
| `admin_user_role` | 用户-角色关联 |
| `admin_role_permission` | 角色-权限关联 |
| `admin_role_menu` | 角色-菜单关联 |
| `admin_operation_logs` | 系统操作日志 |
| `admin_login_logs` | 登录日志 |
| `admin_notifications` | 系统通知 |
| `admin_user_notifications` | 用户通知关联 |
| `system_settings` | 系统设置表 |

### 任务调度表

| 表名 | 说明 |
|------|------|
| `projects` | 项目表 |
| `project_user` | 项目成员表 |
| `tasks` | 定时任务表 |
| `task_logs` | 任务执行日志表 |
| `task_log_details` | 执行日志详情表 |
| `nodes` | 执行节点表 |
| `notification_channels` | 通知渠道表 |
| `task_notifications` | 任务通知关联表 |
| `notification_logs` | 通知发送日志表 |

### 队列表

| 表名 | 说明 |
|------|------|
| `jobs` | 队列表 |
| `failed_jobs` | 失败队列表 |

---

## Artisan 命令

| 命令 | 说明 |
|------|------|
| `php artisan scheduler:run` | 执行一次任务调度扫描 |
| `php artisan scheduler:daemon` | 启动调度守护进程（秒级） |
| `php artisan schedule:list` | 查看已注册的调度任务 |
| `php artisan queue:work` | 启动队列工作进程 |
| `php artisan queue:listen` | 监听队列（开发用） |
| `php artisan queue:failed` | 查看失败的队列任务 |
| `php artisan queue:retry all` | 重试所有失败任务 |

---

## 权限体系

基于 RBAC 模型，由 `admin/permission` 包提供：

- **用户** → 分配角色
- **角色** → 分配菜单和权限
- **权限** → 接口级别控制（基于路由名称）
- **菜单** → 前端菜单显示
- **超级管理员** (`super-admin`) → 拥有所有权限，跳过权限验证

### 中间件

| 别名 | 说明 |
|------|------|
| `auth:sanctum` | Token 认证 |
| `permission` | 权限验证（基于路由名称） |
| `operation.log` | 操作日志记录 |

### 调度模块权限标识

```
项目管理:    projects.index, projects.store, projects.show, projects.update, projects.destroy, projects.members
任务管理:    tasks.index, tasks.store, tasks.show, tasks.update, tasks.destroy, tasks.trigger, tasks.pause, tasks.resume, tasks.logs
执行日志:    logs.index, logs.show, logs.archive
节点管理:    nodes.index, nodes.store, nodes.show, nodes.update, nodes.destroy
通知渠道:    notification-channels.index, notification-channels.store, notification-channels.show, notification-channels.update, notification-channels.destroy
```

---

## 开发命令

```bash
# 运行测试
composer test

# 代码风格检查
./vendor/bin/pint

# 查看日志（实时）
php artisan pail --timeout=0

# 清除缓存
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

---

## 生产环境部署

### 优化配置

```bash
# 配置缓存
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 设置文件权限
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

### Supervisor 配置

**队列 worker：**

```ini
[program:taskflow-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/taskFlowApi/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/path/to/taskFlowApi/storage/logs/queue.log
stopwaitsecs=3600
```

**调度守护进程（秒级调度用）：**

```ini
[program:taskflow-scheduler]
process_name=%(program_name)s
command=php /path/to/taskFlowApi/artisan scheduler:daemon --sleep=1
autostart=true
autorestart=true
user=www-data
redirect_stderr=true
stdout_logfile=/path/to/taskFlowApi/storage/logs/scheduler.log
```

---

## 前端项目

前端代码位于 `../taskFlowFrontend/`，基于：

- Vue 3 + Composition API
- TypeScript
- Vite
- Element Plus
- Pinia
- Vue Router

详见 [taskFlowFrontend/README.md](../taskFlowFrontend/README.md)

---

## 默认账号

| 角色 | 用户名 | 密码 |
|------|--------|------|
| 超级管理员 | super | gzrbbks |
| 管理员 | admin | 123456 |

---

## License

MIT
