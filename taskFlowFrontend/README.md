# 任务流调度平台 - 前端

基于 **Vue 3 + TypeScript + Vite + Element Plus** 构建的定时任务调度管理系统前端。

---

## 技术栈

| 类别 | 技术 | 版本 |
|------|------|------|
| 框架 | Vue 3 | ^3.5.13 |
| 语言 | TypeScript | ~5.8.3 |
| 构建 | Vite | ^6.3.5 |
| UI 组件 | Element Plus | ^2.12.0 |
| 路由 | vue-router | ^4.6.3 |
| 状态管理 | Pinia | ^3.0.2 |
| HTTP | axios | ^1.9.0 |
| 国际化 | vue-i18n | ^11.1.3 |
| 图表 | echarts | ^6.0.0 |
| 日期 | dayjs | ^1.11.13 |

---

## 快速开始

### 环境要求

- Node.js >= 18
- 后端服务已启动（默认 http://127.0.0.1:8000）

### 安装依赖

```bash
cd taskFlowFrontend
npm install
```

### 开发运行

```bash
npm run dev
```

浏览器访问 `http://localhost:3001`

### 构建部署

```bash
npm run build    # 构建输出在 dist/ 目录
npm run preview  # 预览构建产物
```

---

## 项目结构

```
src/
├── api/                     # API 接口层（按模块拆分）
│   ├── axios.ts             # 请求实例（拦截器、Token、错误处理）
│   ├── auth.ts              # 登录/登出/用户信息/Token刷新
│   ├── menu.ts              # 菜单管理
│   ├── permission.ts        # 权限管理
│   ├── role.ts              # 角色管理
│   ├── user.ts              # 用户管理
│   ├── project.ts           # 项目管理
│   ├── task.ts              # 定时任务管理
│   ├── taskLog.ts           # 任务执行日志
│   ├── node.ts              # Shell节点管理
│   ├── notificationChannel.ts  # 通知渠道管理
│   └── upload.ts            # 文件上传
├── components/              # 公共组件
│   ├── Pagination/          # 分页组件
│   └── WangEditor/          # 富文本编辑器
├── composables/             # 组合式函数
│   ├── useTable.ts          # 表格通用逻辑（分页/搜索/加载）
│   └── useForm.ts           # 表单通用逻辑
├── directives/              # 自定义指令
│   ├── permission.ts        # v-permission 权限控制
│   ├── draggable.ts         # v-draggable 弹窗拖拽
│   └── index.ts             # 指令注册入口
├── layout/                  # 布局组件
│   ├── components/
│   │   ├── Navbar/          # 顶部导航栏（面包屑/全屏/语言/通知/用户）
│   │   ├── Sidebar/         # 侧边栏菜单
│   │   └── TagsView/        # 多标签页导航
│   └── index.vue            # 布局入口
├── locales/                 # 国际化
│   ├── zh-CN.ts             # 中文语言包
│   └── en.ts                # 英文语言包
├── router/                  # 路由
│   ├── index.ts             # 常量路由 + 动态路由注入
│   └── guard.ts             # 路由守卫（认证/权限/动态路由）
├── stores/                  # Pinia 状态管理
│   ├── auth.ts              # 认证状态（Token/用户信息/角色）
│   ├── permission.ts        # 权限状态（菜单/权限/动态路由生成）
│   ├── app.ts               # 应用设置（主题/语言/侧边栏）
│   └── tagsView.ts          # 标签页视图状态
├── styles/                  # 样式文件
├── types/                   # TypeScript 类型定义
├── views/                   # 页面视图
│   ├── dashboard/           # 仪表盘
│   ├── login/               # 登录页
│   ├── profile/             # 个人信息/修改密码
│   ├── system/              # 系统管理（用户/角色/权限/菜单）
│   ├── logs/                # 系统日志（登录日志/操作日志）
│   └── scheduler/           # ★ 定时任务调度模块
│       ├── projects/        # 项目管理
│       ├── tasks/           # 任务管理
│       ├── nodes/           # 节点管理
│       ├── task-logs/       # 执行日志
│       ├── notification-channels/  # 通知渠道
│       └── operation-logs/  # 操作日志
└── main.ts                  # 应用入口
```

---

## 路由说明

### 常量路由（静态定义）

| 路径 | 页面 | 说明 |
|------|------|------|
| `/login` | 登录页 | 免认证 |
| `/dashboard` | 仪表盘 | 固定标签页 |
| `/profile` | 个人信息 | 修改个人资料 |
| `/password` | 修改密码 | 密码变更 |

### 动态路由机制

除常量路由外，所有菜单均由后端 API 动态生成：

1. 登录成功后，前端调用 `getUserMenus()` 获取用户可见菜单树
2. 通过 `import.meta.glob('@/views/**/*.vue')` 动态匹配页面组件
3. 路由注册在 `Layout` 父路由下，共享侧边栏和顶栏
4. 超管（`super-admin`）自动拥有全部菜单

---

## 定时任务调度模块使用说明

### 模块概览

任务调度系统基于 RBAC 权限体系运行，需要在「角色管理」中为角色分配对应的菜单和权限。系统提供 6 个子模块：

---

### 1. 项目管理

**路径**：`/scheduler/projects`

**功能**：
- 项目 CRUD（创建/编辑/删除）
- 按项目名称/编码/状态搜索
- 查看项目成员

**操作流程**：
1. 点击「创建」按钮，填写项目名称、编码和描述
2. 选择项目负责人（从已登录用户中选择）
3. 设置启用/禁用状态
4. 项目管理是任务管理的前置条件——创建任务时需要绑定项目

**相关权限**：
- `projects.index` — 查看列表
- `projects.store` — 创建项目
- `projects.update` — 编辑项目
- `projects.destroy` — 删除项目
- `projects.members` — 管理成员

---

### 2. 任务管理

**路径**：`/scheduler/tasks`

**功能**：
- 任务 CRUD（创建/编辑/删除）
- 手动触发执行
- 暂停/恢复任务
- 按项目/名称/执行器类型/状态搜索
- 查看任务执行日志

**执行器类型**：

| 类型 | 说明 | 配置示例 |
|------|------|----------|
| HTTP | HTTP回调 | `{"url": "https://api.example.com/callback", "method": "POST", "headers": {}}` |
| Shell | Agent节点执行 | `{"command": "/opt/scripts/backup.sh", "node_id": "xxx"}` |
| Job | 队列任务 | `{"job_class": "App\\Jobs\\SyncData", "params": {}}` |
| MQ | 消息队列 | `{"topic": "order_sync", "payload": {}}` |

**Cron 表达式**：

支持 **5位**（分 时 日 月 周）和 **6位**（秒 分 时 日 月 周）两种格式：

**5位格式示例：**

| 表达式 | 说明 |
|--------|------|
| `* * * * *` | 每分钟 |
| `*/5 * * * *` | 每5分钟 |
| `*/15 * * * *` | 每15分钟 |
| `*/30 * * * *` | 每30分钟 |
| `0 * * * *` | 每小时整点 |
| `0 */2 * * *` | 每2小时 |
| `0 0 * * *` | 每天午夜0点 |
| `0 9 * * *` | 每天上午9点 |
| `0 9 * * 1-5` | 工作日上午9点 |
| `0 0 1 * *` | 每月1日午夜 |
| `0 0 * * 0` | 每周日午夜 |

**6位格式示例：**

| 表达式 | 说明 |
|--------|------|
| `*/30 * * * * *` | 每30秒 |
| `*/10 * * * * *` | 每10秒 |
| `0 * * * * *` | 每分钟 |
| `0 */5 * * * *` | 每5分钟 |
| `0 0 * * * *` | 每小时 |
| `30 * * * * *` | 每小时的第30秒 |

**关键配置项**：

| 配置项 | 说明 | 默认值 |
|--------|------|--------|
| 超时时间 | 任务最大执行时间（秒） | 300 |
| 重试次数 | 失败后重试次数 | 0 |
| 重试间隔 | 重试间隔（秒） | 60 |
| 优先级 | 数字越大优先级越高 | 0 |
| 并发策略 | `allow`允许 / `forbid`禁止 / `replace`替换 | forbid |
| 失火策略 | `skip`跳过 / `fire_once`执行一次 / `fire_all`执行全部 | skip |
| 状态 | `enabled`启用 / `disabled`禁用 / `paused`暂停 | enabled |

**操作流程**：
1. 点击「创建」按钮
2. 选择所属项目（从项目列表中选择）
3. 填写任务名称和 Cron 表达式
4. 选择执行器类型并填写配置（JSON 格式）
5. 设置超时、重试、优先级等参数
6. 点击「确定」创建

**行内操作**：
- **编辑** — 修改任务配置
- **执行** — 手动触发一次执行
- **暂停/恢复** — 控制调度状态
- **日志** — 跳转到该任务的执行日志
- **删除** — 删除任务

---

### 3. 节点管理

**路径**：`/scheduler/nodes`

**功能**：
- Shell 执行节点 CRUD
- 按名称/IP/状态搜索
- 查看节点在线状态、心跳时间、资源信息

**节点字段说明**：

| 字段 | 说明 |
|------|------|
| 节点名称 | 用于识别的名称 |
| IP地址 | 节点IP |
| Agent端口 | Agent服务端口（默认9501） |
| Agent Token | Agent通信密钥（自动生成32位） |
| 命令白名单 | 允许执行的命令路径前缀（如 `/opt/scripts/`） |

**操作流程**：
1. 点击「添加节点」按钮
2. 填写节点名称和IP地址
3. Token 自动生成（也可手动输入）
4. 可选填写命令白名单路径
5. 在目标机器上安装 Agent 并配置对应 Token

**相关权限**：
- `nodes.index` / `nodes.store` / `nodes.update` / `nodes.destroy`

---

### 4. 执行日志

**路径**：`/scheduler/task-logs`

**功能**：
- 查看所有任务的执行历史
- 按任务/状态/触发类型/日期范围搜索
- 查看日志详情（输出内容、错误信息）
- 支持 HTML/Markdown 渲染
- 归档日志查询

**日志状态**：

| 状态 | 说明 |
|------|------|
| running | 执行中 |
| success | 执行成功 |
| failed | 执行失败 |
| timeout | 执行超时 |
| cancelled | 手动取消 |

**触发类型**：
- `cron` — 定时触发
- `manual` — 手动触发
- `retry` — 重试触发

**相关权限**：
- `logs.index` — 查看列表
- `logs.show` — 查看详情
- `logs.archive` — 查询归档

---

### 5. 通知渠道

**路径**：`/scheduler/notification-channels`

**功能**：
- 通知渠道 CRUD
- 按类型/状态搜索

**渠道类型**：

| 类型 | 说明 | 配置示例 |
|------|------|----------|
| Email | 邮件通知 | `{"to": ["admin@example.com"], "cc": [], "subject_template": "任务执行通知"}` |
| Webhook | HTTP回调通知 | `{"url": "https://hooks.example.com/notify", "secret": ""}` |
| 钉钉 | 钉钉机器人 | `{"webhook_url": "https://oapi.dingtalk.com/robot/send?access_token=xxx", "secret": ""}` |
| 企业微信 | 企业微信机器人 | `{"webhook_url": "https://qyapi.weixin.qq.com/cgi-bin/webhook/send?key=xxx"}` |
| 飞书 | 飞书机器人 | `{"webhook_url": "https://open.feishu.cn/open-apis/bot/v2/hook/xxx"}` |

**操作流程**：
1. 点击「创建」按钮
2. 填写渠道名称
3. 选择渠道类型
4. 配置渠道参数（JSON 格式）
5. 在任务管理中绑定通知渠道

**相关权限**：
- `notification-channels.index` / `notification-channels.store` / `notification-channels.update` / `notification-channels.destroy`

---

## 权限配置指南

### 初次配置步骤

1. **菜单初始化**：通过 `scheduler_init.php` 脚本自动创建菜单和权限数据
2. **角色授权**：进入「系统管理 → 角色管理」，编辑目标角色
3. **分配菜单**：在「分配菜单」中勾选「任务调度」目录
4. **分配权限**：在「分配权限」中勾选对应的 `projects.*`、`tasks.*`、`nodes.*` 等权限

### 权限标识列表

```
项目管理:    projects.index, projects.store, projects.show, projects.update, projects.destroy, projects.members
任务管理:    tasks.index, tasks.store, tasks.show, tasks.update, tasks.destroy, tasks.trigger, tasks.pause, tasks.resume, tasks.logs
节点管理:    nodes.index, nodes.store, nodes.show, nodes.update, nodes.destroy
执行日志:    logs.index, logs.show, logs.archive
通知渠道:    notification-channels.index, notification-channels.store, notification-channels.show, notification-channels.update, notification-channels.destroy
```

---

## 自定义指令

### `v-permission`

控制元素显隐，基于用户权限标识：

```vue
<el-button v-permission="['tasks.store']">创建任务</el-button>
<el-button v-permission="['tasks.destroy']">删除任务</el-button>
```

### `v-draggable`

使 Element Plus Dialog 支持拖拽：

```vue
<el-dialog v-model="visible" v-draggable title="创建任务">
  ...
</el-dialog>
```

---

## 环境变量

| 变量 | 开发环境 | 说明 |
|------|----------|------|
| `VITE_APP_TITLE` | `任务流调度平台` | 页面标题后缀 |
| `VITE_APP_VERSION` | `1.0.0` | 应用版本号 |
| `VITE_API_BASE_URL` | 空 | API基础路径（空则使用 Vite 代理） |
| `VITE_UPLOAD_URL` | `http://127.0.0.1:8001` | 文件上传地址 |

Vite 代理配置（`/admin`、`/v1` → `http://127.0.0.1:8000`），开发时无需设置 `VITE_API_BASE_URL`。

---

## 认证流程

1. 用户访问任意页面 → 路由守卫检查 Token
2. 无 Token → 跳转 `/login?redirect=原路径`
3. 登录成功 → 保存 Token 到 `localStorage`
4. 调用 `getUserMenus()` 获取菜单树
5. 生成动态路由并注册到 vue-router
6. 每次请求自动在 Header 中携带 `Authorization: Bearer {token}`
7. Token 过期（401）→ 弹出重新登录提示
8. 页面刷新 → 从 `localStorage` 恢复状态

---

## 任务调度器启动说明

定时任务需要启动后端调度器才能自动执行。根据精度要求选择以下方式：

### 方式一：分钟级调度（推荐生产环境）

使用 Laravel 调度器 + 系统 Cron，适用于分钟级及以上的任务：

**1. 启动队列工作进程（必须）**

任务通过队列异步执行，必须先启动队列 worker：

```bash
cd taskFlowApi
php artisan queue:work --tries=3
```

生产环境建议使用 Supervisor 管理队列进程。

**2. 配置系统 Cron（每分钟触发调度器）**

在服务器上添加 Cron 任务：

```bash
crontab -e
```

添加以下内容：

```
* * * * * cd /path/to/taskFlowApi && php artisan schedule:run >> /dev/null 2>&1
```

这会每分钟执行一次 `scheduler:run` 命令，扫描并触发所有到期的任务。

**适用 Cron 表达式**：5位或6位均可，但最小精度为1分钟。

---

### 方式二：秒级调度（守护进程）

如果需要秒级精度（如每30秒执行一次），使用守护进程模式：

```bash
cd taskFlowApi
php artisan scheduler:daemon
```

可选参数：
- `--sleep=1` — 扫描间隔（秒），默认1秒，范围1-60

```bash
# 每2秒扫描一次
php artisan scheduler:daemon --sleep=2
```

**注意**：
- 守护进程需要常驻内存，生产环境建议使用 Supervisor 管理
- 仍需启动队列工作进程（`php artisan queue:work`）
- 6位 Cron 表达式（含秒）需要使用此模式

---

### 手动触发调度

测试时可手动执行一次调度扫描：

```bash
cd taskFlowApi
php artisan scheduler:run
```

---

### 调度器工作原理

1. 调度器扫描所有 `status = enabled` 的任务
2. 比较当前时间与 `next_run_at`，触发所有到期任务
3. 根据 `concurrency_strategy` 处理并发：
   - `forbid`（默认）：如果已有运行中的实例，跳过本次触发
   - `allow`：允许并发执行
   - `replace`：终止前一个，启动新的
4. 触发后重新计算并更新 `next_run_at`
5. 任务通过队列异步执行，执行结果写入 `task_logs` 表

---

## 后端项目

后端代码位于 `../taskFlowApi/`，基于 Laravel 12 + PHP 8.2。

**启动后端**：
```bash
cd ../taskFlowApi
php artisan serve --port=8000
```
