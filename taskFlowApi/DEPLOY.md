# taskFlowApi 部署文档

## 环境要求

| 软件 | 版本要求 |
|------|---------|
| PHP | >= 8.2 |
| Composer | >= 2.0 |
| MySQL | >= 8.0 |
| Redis | >= 6.0 (用于队列) |
| Nginx | >= 1.18 |
| Supervisor | >= 4.0 |

## PHP 扩展要求

```bash
# 安装 PHP 8.3 及扩展
apt install php8.3 php8.3-cli php8.3-fpm \
    php8.3-mysql php8.3-redis php8.3-xml php8.3-mbstring \
    php8.3-intl php8.3-zip php8.3-gd php8.3-fileinfo \
    php8.3-tokenizer php8.3-bcmath php8.3-curl \
    php8.3-opcache php8.3-ldap
```

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

### 方式一：传统部署（推荐生产环境）

#### 1. 上传项目到服务器

```bash
# 项目目录
/home/www/taskFlowApi

# 日志目录
/home/logs
```

#### 2. 安装依赖

```bash
cd /home/www/taskFlowApi
composer install
```

#### 3. 配置环境文件

```bash
cp .env.example .env
php artisan key:generate
```

编辑 `.env` 配置数据库和 Redis 连接：

```env
# 主数据库
DB_CONNECTION=mysql
DB_HOST=10.1.16.34
DB_PORT=3306
DB_DATABASE=task_flow
DB_USERNAME=root
DB_PASSWORD=your_password

# 评论数据库
COMMENT_DB_HOST=10.1.4.28
COMMENT_DB_PORT=3306
COMMENT_DB_DATABASE=comment
COMMENT_DB_USERNAME=root
COMMENT_DB_PASSWORD=your_password

# 统计数据库
STATICS_DB_HOST=10.1.4.23
STATICS_DB_PORT=3306
STATICS_DB_DATABASE=statics
STATICS_DB_USERNAME=root
STATICS_DB_PASSWORD=your_password

# Redis（队列驱动）
REDIS_CLIENT=phpredis
REDIS_HOST=10.1.16.34
REDIS_PASSWORD=your_password
REDIS_PORT=6379
QUEUE_CONNECTION=redis
```

#### 4. 创建日志目录并设置权限

```bash
# 创建日志目录
mkdir -p /home/logs
chown www-data:www-data /home/logs

# 设置项目权限
chown -R www-data:www-data /home/www/taskFlowApi
chmod -R 775 /home/www/taskFlowApi/storage
chmod -R 775 /home/www/taskFlowApi/bootstrap/cache
```

#### 5. Nginx 配置

创建 `/etc/nginx/conf.d/taskflow-api.conf`：

```nginx
server {
    listen 8080;
    server_name _;

    root /home/www/taskFlowApi/public;
    index index.php index.html;

    charset utf-8;

    # 日志配置
    access_log  /home/logs/taskflow_api_access.log;
    error_log   /home/logs/taskflow_api_error.log;

    # 最大上传大小
    client_max_body_size 50M;

    # 安全头
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # 静态文件缓存
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|woff2?|svg|ttf|eot)$ {
        expires 30d;
        access_log off;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    # 禁止访问隐藏文件
    location ~ /\.(?!well-known).* {
        deny all;
    }

    # PHP 处理（注意：使用 Unix Socket）
    location ~ \.php$ {
        try_files $uri =404;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;

        # 超时设置
        fastcgi_connect_timeout 300;
        fastcgi_send_timeout 300;
        fastcgi_read_timeout 300;

        # 缓冲区设置
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
        fastcgi_busy_buffers_size 256k;
    }
}
```

测试并重载 Nginx：

```bash
nginx -t && nginx -s reload
```

#### 6. Supervisor 配置

创建 `/etc/supervisor/conf.d/taskflow.conf`（单文件统一管理调度器和队列 worker）：

```ini
; ============================================================
; taskFlowApi - Supervisor 进程管理配置
; ============================================================
; 包含以下进程：
;   1. taskflow-scheduler - 任务调度器（秒级调度）
;   2. taskflow-queue     - 队列工作进程（处理异步任务）
; ============================================================

; -----------------------------------------------------------
; 1. 任务调度器进程
; -----------------------------------------------------------
; 功能：轮询检查任务表，触发到期任务
; 调度精度：2秒（由 --sleep 参数控制）
; 进程数：1（调度器只能有一个主进程）
; -----------------------------------------------------------
[program:taskflow-scheduler]
command=php /home/www/taskFlowApi/artisan scheduler:daemon --sleep=2
process_name=%(program_name)s
numprocs=1
autostart=true          ; Supervisor 启动时自动启动该进程
autorestart=true        ; 进程意外退出时自动重启
user=www-data           ; 运行用户（与 Nginx/PHP-FPM 保持一致）
stdout_logfile=/home/logs/taskflow_scheduler.log
stdout_logfile_maxbytes=50MB    ; 单个日志文件最大 50MB
stdout_logfile_backups=5        ; 保留 5 个轮转日志文件
redirect_stderr=true    ; 将 stderr 重定向到 stdout 日志

; -----------------------------------------------------------
; 2. 队列工作进程
; -----------------------------------------------------------
; 功能：消费 Redis 队列中的任务，执行 Job 类
; 并发数：2个 worker 进程（可根据服务器配置调整）
; 重试次数：3次（任务失败后自动重试 3 次）
; 超时时间：120秒（单个任务最长执行时间）
; 休眠时间：3秒（队列空时休眠 3 秒再检查）
; -----------------------------------------------------------
[program:taskflow-queue]
command=php /home/www/taskFlowApi/artisan queue:work --sleep=3 --tries=3 --timeout=120
process_name=%(program_name)s_%(process_num)02d
numprocs=2              ; worker 进程数量，根据服务器负载调整
autostart=true          ; Supervisor 启动时自动启动
autorestart=true        ; 进程意外退出时自动重启
user=www-data           ; 运行用户
stdout_logfile=/home/logs/taskflow_queue.log
stdout_logfile_maxbytes=50MB    ; 单个日志文件最大 50MB
stdout_logfile_backups=5        ; 保留 5 个轮转日志文件
redirect_stderr=true    ; 将 stderr 重定向到 stdout 日志

; ============================================================
; 运维命令参考
; ============================================================
; 查看状态:    supervisorctl status
; 重启全部:    supervisorctl restart taskflow-queue:* taskflow-scheduler
; 重启队列:    supervisorctl restart taskflow-queue:*
; 重启调度:    supervisorctl restart taskflow-scheduler
; 停止全部:    supervisorctl stop taskflow-queue:* taskflow-scheduler
; 启动全部:    supervisorctl start taskflow-queue:* taskflow-scheduler
; 重载配置:    supervisorctl reread && supervisorctl update
; ============================================================
```

启动 Supervisor：

```bash
supervisorctl reread
supervisorctl update
supervisorctl status
```

#### 7. 配置定时任务

```bash
# 添加到 www-data用户的 crontab
echo "* * * * * cd /home/www/taskFlowApi && php artisan schedule:run >> /home/logs/taskflow_scheduler.log 2>&1" | crontab -u www-data -
```

#### 8. 配置开机自启

```bash
# 启用所有服务开机自启
systemctl enable nginx
systemctl enable php8.3-fpm
systemctl enable supervisor
systemctl enable mysql
systemctl enable redis-server

# 验证开机自启状态
systemctl is-enabled nginx php8.3-fpm supervisor mysql redis-server
```

#### 9. 验证部署

```bash
# 验证服务运行
supervisorctl status

# 验证进程
pgrep -af "queue:work|scheduler:daemon|php-fpm"

# 验证访问
curl -I http://服务器IP:8080/

# 查看 Laravel 版本
cd /home/www/taskFlowApi && php artisan --version
```

---

### 方式二：Docker 部署

#### 2.1 构建镜像

```bash
docker build -t taskflow-api:latest .
```

#### 2.2 使用 docker-compose

```bash
docker compose up -d
```

---

### 方式三：开发环境

```bash
# 启动 API 服务
php artisan serve --host=0.0.0.0 --port=8000

# 启动队列监听
php artisan queue:work

# 启动定时任务守护进程
php artisan scheduler:daemon
```

---

## 数据库初始化

### 运行迁移

```bash
php artisan migrate
```

### 运行数据填充

```bash
# 填充 RBAC 权限数据
php artisan db:seed --class=AdminPermissionSeeder

# 填充任务调度示例数据
php artisan db:seed --class=SchedulerSeeder
```

---

## 常用运维命令

### 查看服务状态

```bash
# Supervisor 进程状态
supervisorctl status

# 查看队列进程
pgrep -af "queue:work"

# 查看调度进程
pgrep -af "scheduler:daemon"

# 查看定时任务列表
php artisan schedule:list
```

### 查看日志

```bash
# 实时查看队列日志
tail -f /home/logs/taskflow_queue.log

# 实时查看调度日志
tail -f /home/logs/taskflow_scheduler.log

# 查看 Laravel 错误日志
tail -f /home/www/taskFlowApi/storage/logs/laravel.log

# 查看 Nginx 访问日志
tail -f /home/logs/taskflow_api_access.log

# 查看 Nginx 错误日志
tail -f /home/logs/taskflow_api_error.log
```

### 重启服务

```bash
# 重启队列 worker
supervisorctl restart taskflow-queue:*

# 重启调度进程
supervisorctl restart taskflow-scheduler:*

# 重启所有服务
supervisorctl restart all

# 重载 Nginx
nginx -s reload

# 重启 PHP-FPM
systemctl restart php8.3-fpm
```

### 清除缓存

```bash
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# 生产环境重新缓存
php artisan config:cache
php artisan route:cache
```

---

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
