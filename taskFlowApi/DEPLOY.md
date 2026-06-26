# taskFlowApi 部署文档

## 环境要求

| 软件 | 版本要求 |
|------|---------|
| PHP | >= 8.2 |
| Composer | >= 2.0 |
| MySQL | >= 8.0 |
| Redis | >= 6.0 (可选，用于队列) |

## 项目结构

```
taskFlowApi/
├── app/
│   ├── Console/Commands/     # Artisan 命令
│   │   ├── SchedulerDaemon.php   # 定时任务守护进程（秒级调度）
│   │   └── SchedulerRun.php     # 定时任务执行器
│   ├── Http/Controllers/      # 控制器
│   ├── Jobs/                  # 队列任务
│   │   ├── ExecuteTask.php        # 任务执行器
│   │   └── ProcessCommentStatistics.php  # 评论统计任务
│   ├── Models/               # 数据模型
│   └── ...
├── database/
│   ├── migrations/           # 数据库迁移
│   └── seeders/              # 数据填充
├── packages/
│   └── admin-permission/     # RBAC 权限包
├── routes/
│   ├── web.php           # Web 路由
│   ├── api.php           # API 路由
│   ├── scheduler.php     # 调度任务路由
│   └── console.php       # 控制台命令
├── config/                # 配置文件
├── .env                   # 环境配置
└── ...
```

## 部署步骤

### 1. 环境准备

#### 1.1 安装 PHP 依赖

```bash
cd taskFlowApi
composer install
```

#### 1.2 创建环境配置文件

```bash
cp .env.example .env
```

#### 1.3 生成应用密钥

```bash
php artisan key:generate
```

### 2. 数据库配置

编辑 `.env` 文件，配置数据库连接：

```env
# 默认数据库（主库）
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=taskflow
DB_USERNAME=root
DB_PASSWORD=your_password

# 评论库（读取评论数据）
COMMENT_DB_HOST=127.0.0.1
COMMENT_DB_PORT=3306
COMMENT_DB_DATABASE=comment
COMMENT_DB_USERNAME=root
COMMENT_DB_PASSWORD=your_password

# 统计库（存储统计数据）
STATICS_DB_HOST=127.0.0.1
STATICS_DB_PORT=3306
STATICS_DB_DATABASE=statics
STATICS_DB_USERNAME=root
STATICS_DB_PASSWORD=your_password

# Redis 配置（队列驱动）
REDIS_CLIENT=phpredis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# 队列配置
QUEUE_CONNECTION=database
```

### 3. 数据库初始化

#### 3.1 运行迁移

```bash
php artisan migrate
```

#### 3.2 运行数据填充

```bash
# 填充 RBAC 权限数据
php artisan db:seed --class=AdminPermissionSeeder

# 填充任务调度示例数据
php artisan db:seed --class=SchedulerSeeder
```

### 4. 缓存配置

```bash
# 清除配置缓存
php artisan config:clear

# 清除路由缓存
php artisan route:clear

# 清除视图缓存
php artisan view:clear

# 重新缓存配置（生产环境）
php artisan config:cache
php artisan route:cache
```

### 5. 存储链接

```bash
php artisan storage:link
```

### 6. 启动服务

#### 6.1 开发环境

```bash
# 启动 API 服务（端口 8000）
php artisan serve --host=0.0.0.0 --port=8000

# 启动队列监听（后台运行）
php artisan queue:work

# 启动定时任务守护进程（秒级调度，可选）
php artisan scheduler:daemon
```

#### 6.2 生产环境（推荐使用 Supervisor）

创建 `/etc/supervisor/conf.d/taskflow.conf`：

```ini
[program:taskflow-api]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/taskFlowApi/artisan serve --host=0.0.0.0 --port=8000
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/taskFlowApi/storage/logs/api.log
stopwaitsecs=3600

[program:taskflow-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/taskFlowApi/artisan queue:work --sleep=3 --tries=3
queue=default
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/path/to/taskFlowApi/storage/logs/queue.log
stopwaitsecs=3600

[program:taskflow-scheduler]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/taskFlowApi/artisan scheduler:daemon
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/path/to/taskFlowApi/storage/logs/scheduler.log
stopwaitsecs=3600
```

启动 Supervisor：

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start taskflow-api:*
sudo supervisorctl start taskflow-queue:*
sudo supervisorctl start taskflow-scheduler:*
```

### 7. 定时任务配置

#### 7.1 分钟级定时任务（Crontab）

添加以下行到 crontab (`crontab -e`)：

```bash
* * * * * cd /path/to/taskFlowApi && php artisan schedule:run >> /dev/null 2>&1
```

#### 7.2 秒级定时任务

秒级任务使用 `scheduler:daemon` 命令，需要通过 Supervisor 管理（如上所示）。

### 8. Nginx 配置示例

```nginx
server {
    listen 80;
    server_name api.taskflow.com;
    root /path/to/taskFlowApi/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPTFILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

## 数据库连接说明

本项目使用多个数据库连接：

| 连接名 | 用途 | 默认数据库 |
|--------|------|-----------|
| mysql (默认) | 主库，存储用户、角色、权限、任务等核心数据 | taskflow |
| mysql_comment | 评论库，读取去重后的评论统计数据 | comment |
| mysql_statics | 统计库，存储评论统计结果 | statics |

### comment_statics 表结构

如果统计库中不存在 `comment_statics` 表，请执行以下 SQL：

```sql
CREATE TABLE `comment_statics` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT '主键ID',
  `news_id` bigint(20) DEFAULT NULL COMMENT '新闻ID',
  `num` int(11) DEFAULT NULL COMMENT '数量',
  `created_at` timestamp NULL DEFAULT NULL COMMENT '创建时间',
  `updated_at` timestamp NULL DEFAULT NULL COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_news_id` (`news_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## API 接口文档

### 基础信息

- 基础路径：`/admin` 或 `/v1`
- 认证方式：Laravel Sanctum Token
- 响应格式：`{ data, message, status_code }`

### 认证接口

| 方法 | 路径 | 说明 |
|------|------|------|
| POST | /admin/auth/login | 用户登录 |
| POST | /admin/auth/logout | 用户登出 |
| GET | /admin/auth/me | 获取当前用户信息 |

### 调度任务接口

| 方法 | 路径 | 说明 |
|------|------|------|
| GET | /v1/projects | 项目列表 |
| POST | /v1/projects | 创建项目 |
| PUT | /v1/projects/{hashId} | 更新项目 |
| DELETE | /v1/projects/{hashId} | 删除项目 |
| GET | /v1/tasks | 任务列表 |
| POST | /v1/tasks | 创建任务 |
| PUT | /v1/tasks/{hashId} | 更新任务 |
| DELETE | /v1/tasks/{hashId} | 删除任务 |
| POST | /v1/tasks/{hashId}/execute | 执行任务 |
| GET | /v1/task-logs | 执行日志列表 |
| GET | /v1/task-logs/{hashId} | 执行日志详情 |

## 常见问题

### 1. 队列任务不执行

检查队列表是否创建：

```bash
php artisan queue:table
php artisan migrate
```

确保 `queue:work` 进程正在运行：

```bash
ps aux | grep "queue:work"
```

### 2. 定时任务不执行

检查 crontab 是否配置正确：

```bash
crontab -l
```

检查调度任务是否正确注册：

```bash
php artisan schedule:list
```

### 3. 数据库连接失败

确认数据库服务正在运行：

```bash
mysql -u root -p -e "SHOW DATABASES;"
```

### 4. 权限问题

确保 storage 目录可写：

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data /path/to/taskFlowApi
```

## 更新部署

### 代码更新

```bash
cd /path/to/taskFlowApi
git pull origin main

# 更新依赖
composer install

# 运行迁移
php artisan migrate

# 清除并重建缓存
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 重启队列和定时任务
sudo supervisorctl restart taskflow-queue:*
sudo supervisorctl restart taskflow-scheduler:*
```

### 数据库迁移

```bash
# 查看待执行迁移
php artisan migrate:status

# 执行迁移
php artisan migrate

# 回滚上一次迁移
php artisan migrate:rollback
```

## 监控与日志

### 日志位置

- API 日志：`storage/logs/api.log`
- 队列日志：`storage/logs/queue.log`
- 调度日志：`storage/logs/scheduler.log`
- Laravel 日志：`storage/logs/laravel.log`

### 查看实时日志

```bash
tail -f storage/logs/laravel.log
tail -f storage/logs/queue.log
```

## 安全建议

1. **生产环境关闭调试模式**

```env
APP_ENV=production
APP_DEBUG=false
```

2. **配置 HTTPS**

3. **定期备份数据库**

4. **限制 API 访问频率**

```env
# 在 .env 中配置限流
RATE_LIMIT_PER_MINUTE=60
```
