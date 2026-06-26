# taskFlowFrontend 部署文档

## 环境要求

| 软件 | 版本要求 |
|------|---------|
| Node.js | >= 18.0 |
| npm | >= 9.0 |
| pnpm | >= 8.0 (可选，推荐) |

## 项目结构

```
taskFlowFrontend/
├── src/
│   ├── api/                  # API 接口封装
│   │   ├── axios.ts          # Axios 实例配置
│   │   ├── auth.ts           # 认证接口
│   │   ├── task.ts           # 任务接口
│   │   ├── taskLog.ts        # 任务日志接口
│   │   └── ...
│   ├── components/           # 公共组件
│   │   ├── Pagination/       # 分页组件
│   │   └── WangEditor/       # 富文本编辑器
│   ├── composables/          # 组合式函数
│   │   ├── useForm.ts        # 表单处理
│   │   └── useTable.ts       # 表格处理
│   ├── constants/            # 常量定义
│   │   └── menu.ts           # 菜单配置
│   ├── directives/           # 自定义指令
│   │   ├── draggable.ts      # 拖拽指令
│   │   └── permission.ts     # 权限指令
│   ├── layout/               # 布局组件
│   │   ├── components/
│   │   │   ├── Navbar/       # 顶部导航
│   │   │   ├── Sidebar/      # 侧边栏
│   │   │   └── TagsView/     # 标签页
│   │   └── index.vue
│   ├── locales/              # 国际化语言包
│   │   ├── zh-CN.ts          # 中文
│   │   └── en.ts             # 英文
│   ├── router/               # 路由配置
│   │   ├── guard.ts          # 路由守卫
│   │   └── index.ts
│   ├── stores/               # Pinia 状态管理
│   │   ├── app.ts            # 应用状态
│   │   ├── auth.ts            # 认证状态
│   │   └── permission.ts     # 权限状态
│   ├── styles/               # 样式文件
│   ├── types/                # TypeScript 类型
│   ├── utils/                # 工具函数
│   ├── views/                # 页面组件
│   │   ├── dashboard/        # 仪表盘
│   │   ├── login/            # 登录页
│   │   ├── logs/             # 日志管理
│   │   ├── notifications/    # 通知管理
│   │   ├── profile/          # 个人中心
│   │   ├── scheduler/        # 任务调度
│   │   │   ├── projects/     # 项目管理
│   │   │   ├── tasks/        # 任务管理
│   │   │   ├── task-logs/    # 执行日志
│   │   │   ├── nodes/        # 节点管理
│   │   │   └── notification-channels/  # 通知渠道
│   │   └── system/           # 系统管理
│   │       ├── users/        # 用户管理
│   │       ├── roles/        # 角色管理
│   │       ├── permissions/  # 权限管理
│   │       └── menus/        # 菜单管理
│   ├── App.vue
│   └── main.ts
├── public/                   # 静态资源
├── index.html
├── vite.config.ts           # Vite 配置
├── tsconfig.json            # TypeScript 配置
└── package.json
```

## 部署步骤

### 1. 安装 Node.js 依赖

```bash
cd taskFlowFrontend

# 使用 npm
npm install

# 或使用 pnpm（推荐，速度更快）
pnpm install
```

### 2. 开发环境运行

```bash
# 启动开发服务器（端口 3001）
npm run dev

# 或使用 pnpm
pnpm dev
```

访问 `http://localhost:3001`，开发服务器会自动代理 `/admin` 和 `/v1` 请求到后端 `http://127.0.0.1:8000`。

### 3. 构建生产版本

```bash
# 构建生产版本
npm run build

# 或使用 pnpm
pnpm build
```

构建产物会输出到 `dist/` 目录。

### 4. 预览生产版本

```bash
# 本地预览生产构建
npm run preview
```

## 环境变量配置

### 开发环境 (.env.development)

```env
VITE_APP_TITLE=TaskFlow Admin
VITE_APP_BASE_API=http://localhost:8000
```

### 生产环境 (.env.production)

```env
VITE_APP_TITLE=TaskFlow Admin
VITE_APP_BASE_API=https://api.taskflow.com
```

### API 地址配置

编辑 `src/api/axios.ts` 中的 baseURL：

```typescript
const baseURL = import.meta.env.VITE_APP_BASE_API || 'http://localhost:8000'
```

## 构建配置

### Vite 配置 (vite.config.ts)

```typescript
export default defineConfig({
  plugins: [
    vue(),
    AutoImport({
      imports: ['vue', 'vue-router', 'pinia', 'vue-i18n'],
      resolvers: [ElementPlusResolver()],
      dts: 'src/auto-imports.d.ts',
    }),
    Components({
      resolvers: [ElementPlusResolver()],
      dts: 'src/components.d.ts',
    }),
  ],
  resolve: {
    alias: {
      '@': resolve(__dirname, 'src'),
    },
  },
  server: {
    port: 3001,
    proxy: {
      '/admin': {
        target: 'http://127.0.0.1:8000',
        changeOrigin: true,
      },
      '/v1': {
        target: 'http://127.0.0.1:8000',
        changeOrigin: true,
      },
    },
  },
})
```

### 修改 API 代理目标

如果后端服务不在本地，修改 `vite.config.ts` 中的 `proxy` 配置：

```typescript
proxy: {
  '/admin': {
    target: 'https://api.taskflow.com',  // 修改为实际后端地址
    changeOrigin: true,
  },
  '/v1': {
    target: 'https://api.taskflow.com',  // 修改为实际后端地址
    changeOrigin: true,
  },
},
```

## Nginx 部署配置

### 单域名部署

如果前后端在同一域名下，使用以下配置：

```nginx
server {
    listen 80;
    server_name taskflow.com;
    root /path/to/taskFlowFrontend/dist;
    index index.html;

    # 开启 gzip
    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml;
    gzip_min_length 1000;

    # SPA 路由支持
    location / {
        try_files $uri $uri/ /index.html;
    }

    # API 代理到后端
    location /admin {
        proxy_pass http://127.0.0.1:8000;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    location /v1 {
        proxy_pass http://127.0.0.1:8000;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    # 静态资源缓存
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

### 前后端分离部署

如果前后端使用不同域名：

**前端 (taskflow.com)**

```nginx
server {
    listen 80;
    server_name taskflow.com;
    root /path/to/taskFlowFrontend/dist;
    index index.html;

    location / {
        try_files $uri $uri/ /index.html;
    }

    # 静态资源缓存
    location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

**后端 (api.taskflow.com)**

```nginx
server {
    listen 80;
    server_name api.taskflow.com;
    root /path/to/taskFlowApi/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPTFILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

## Docker 部署

### Dockerfile

创建 `Dockerfile`：

```dockerfile
# 构建阶段
FROM node:18-alpine as builder

WORKDIR /app

COPY package*.json ./
RUN npm install

COPY . .
RUN npm run build

# 运行阶段
FROM nginx:alpine

COPY --from=builder /app/dist /usr/share/nginx/html
COPY nginx.conf /etc/nginx/conf.d/default.conf

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
```

### nginx.conf

创建 `nginx.conf`：

```nginx
server {
    listen 80;
    server_name localhost;
    root /usr/share/nginx/html;
    index index.html;

    location / {
        try_files $uri $uri/ /index.html;
    }

    # API 代理
    location /admin {
        proxy_pass http://backend:8000;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }

    location /v1 {
        proxy_pass http://backend:8000;
        proxy_http_version 1.1;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

### docker-compose.yml

创建 `docker-compose.yml`：

```yaml
version: '3.8'

services:
  frontend:
    build: .
    ports:
      - "80:80"
    depends_on:
      - backend
    networks:
      - taskflow

  backend:
    image: taskflow-api:latest
    ports:
      - "8000:8000"
    environment:
      - APP_ENV=production
      - APP_DEBUG=false
    volumes:
      - ./taskFlowApi:/var/www/html
    networks:
      - taskflow

networks:
  taskflow:
    driver: bridge
```

### 构建和运行

```bash
# 构建镜像
docker build -t taskflow-frontend .

# 运行容器
docker-compose up -d
```

## 更新部署

### 构建新版本

```bash
# 拉取最新代码
git pull origin main

# 安装依赖
npm install

# 构建生产版本
npm run build
```

### 部署构建产物

```bash
# 方式一：直接替换
rm -rf /var/www/taskflow/dist
cp -r dist /var/www/taskflow/

# 方式二：使用软链接
ln -sfn /path/to/new/dist /var/www/taskflow/dist
```

### 无停机更新（推荐）

```bash
# 1. 构建新版本
npm run build

# 2. 创建新版本目录
mkdir -p /var/www/taskflow/releases/$(date +%Y%m%d%H%M%S)

# 3. 复制新版本
cp -r dist/* /var/www/taskflow/releases/$(date +%Y%m%d%H%M%S)/

# 4. 切换软链接
ln -sfn /var/www/taskflow/releases/$(date +%Y%m%d%H%M%S) /var/www/taskflow/current

# 5. 重载 Nginx
nginx -s reload
```

## 功能模块说明

### 1. 任务调度模块

| 页面 | 路径 | 说明 |
|------|------|------|
| 项目管理 | /scheduler/projects | 任务分组管理 |
| 任务管理 | /scheduler/tasks | 定时任务配置 |
| 执行日志 | /scheduler/task-logs | 任务执行记录 |
| 节点管理 | /scheduler/nodes | 执行节点配置 |
| 通知渠道 | /scheduler/notification-channels | 通知方式配置 |

### 2. 系统管理模块

| 页面 | 路径 | 说明 |
|------|------|------|
| 用户管理 | /system/users | 系统用户管理 |
| 角色管理 | /system/roles | 角色权限配置 |
| 权限管理 | /system/permissions | 权限点配置 |
| 菜单管理 | /system/menus | 菜单结构配置 |

### 3. 日志管理模块

| 页面 | 路径 | 说明 |
|------|------|------|
| 登录日志 | /logs/login-logs | 用户登录记录 |
| 操作日志 | /logs/operation-logs | 操作行为记录 |

## 国际化配置

### 语言切换

系统在右上角提供语言切换按钮，支持中文和英文。

### 添加新语言

1. 在 `src/locales/` 目录下创建新的语言文件，如 `ja.ts`
2. 在 `src/locales/index.ts` 中注册新语言
3. 在 `src/stores/app.ts` 中的 `locale` 选项添加新语言

### 翻译键值命名规范

使用点分隔的键名，格式为 `模块.页面.内容`：

```typescript
export default {
  // 系统管理 - 用户管理
  'system.users.title': '用户管理',
  'system.users.add': '添加用户',
  'system.users.edit': '编辑用户',

  // 任务调度 - 任务管理
  'scheduler.tasks.title': '任务管理',
  'scheduler.tasks.add': '添加任务',

  // 通用
  'common.save': '保存',
  'common.cancel': '取消',
  'common.confirm': '确认',
  'common.delete': '删除',
  'common.edit': '编辑',
}
```

## 常见问题

### 1. 构建失败

检查 Node.js 版本：

```bash
node -v  # 需要 >= 18.0
```

清理缓存后重试：

```bash
rm -rf node_modules dist
npm install
npm run build
```

### 2. API 请求 404

确认后端服务正在运行：

```bash
curl http://127.0.0.1:8000/admin/me
```

检查 API 代理配置：

```typescript
// vite.config.ts
proxy: {
  '/admin': {
    target: 'http://127.0.0.1:8000',  // 确认后端地址
    changeOrigin: true,
  },
},
```

### 3. 页面空白

检查浏览器控制台错误，常见原因：

- API 地址配置错误
- 跨域问题
- 静态资源路径问题

### 4. 样式异常

检查 SCSS 变量是否正确加载：

```typescript
// vite.config.ts
css: {
  preprocessorOptions: {
    scss: {
      additionalData: `@use "@/styles/variables.scss" as *;`,
    },
  },
},
```

## 性能优化

### 1. 路由懒加载

```typescript
// router/index.ts
const routes = [
  {
    path: '/dashboard',
    component: () => import('@/views/dashboard/index.vue'),
  },
]
```

### 2. 组件按需引入

已配置 Element Plus 按需导入，无需手动引入。

### 3. 图片优化

使用 WebP 格式或使用图片 CDN。

### 4. Gzip 压缩

Nginx 配置启用 gzip：

```nginx
gzip on;
gzip_types text/plain text/css application/json application/javascript text/xml application/xml;
gzip_min_length 1000;
```

## 安全建议

1. **配置 CSP 安全策略**

2. **使用 HTTPS**

3. **API 请求添加 Token 验证**

4. **敏感信息不暴露在前端代码中**

5. **定期更新依赖修复安全漏洞**

```bash
# 检查安全漏洞
npm audit

# 自动修复
npm audit fix
```
