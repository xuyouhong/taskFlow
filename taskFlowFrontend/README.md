# RBAC Admin — 前端管理系统

基于 Vue 3 + Element Plus 的 RBAC 权限管理后台前端，配套后端 API 项目为 [laravel-rbac-api](../laravel-rbac-api)。

## 技术栈

- **框架：** Vue 3.5 + TypeScript 5.8
- **构建工具：** Vite 6
- **UI 组件库：** Element Plus 2.12
- **状态管理：** Pinia 3 + pinia-plugin-persistedstate
- **路由：** Vue Router 4（动态路由，从后端菜单生成）
- **HTTP 客户端：** Axios
- **国际化：** vue-i18n 11（中英文切换）
- **图表：** ECharts 6
- **样式：** SCSS + CSS Custom Properties（亮色/暗色主题）

## 功能特性

- **登录系统：** 用户名 + 密码 + 图形验证码登录，JWT Token 认证
- **仪表盘：** 用户统计、登录统计、操作统计、ECharts 趋势图表、最近登录记录、系统信息
- **用户管理：** CRUD、批量启用/禁用、角色分配
- **角色管理：** CRUD、菜单分配（树形选择）、权限分配、批量状态管理
- **权限管理：** CRUD、路由同步（从后端自动同步路由到权限表）、批量状态管理
- **菜单管理：** 树形表格展示、CRUD、图标选择器、菜单排序
- **登录日志：** 查看、搜索过滤、统计分析（ECharts 柱状图）、批量删除
- **操作日志：** 查看、JSON 参数查看器、日志清理、统计分析、批量删除
- **系统通知：** CRUD、发布/撤销工作流、目标用户选择（全部/指定角色/指定用户）
- **个人中心：** 修改个人信息、修改密码
- **布局组件：** 可折叠侧边栏、面包屑导航、菜单搜索（Ctrl+K）、全屏切换、语言切换、主题切换、通知面板、用户头像下拉、TagsView 标签导航

## 权限控制

### 菜单权限（页面级）
- 用户登录后，从后端获取有权限访问的菜单列表
- 动态生成路由并注册到 Vue Router
- 路由守卫检查用户是否有权访问目标页面

### 按钮权限（元素级）
- 使用 `v-permission` 自定义指令控制按钮/操作的可见性
- 用法：`v-permission="['users.store']"` — 如果用户没有对应权限，DOM 元素会被移除
- 权限标识（slug）与后端 Laravel 路由名称一致

## 项目结构

```
src/
├── api/                    # API 请求模块（10 个模块，覆盖 71 个后端接口）
│   ├── axios.ts            # Axios 实例 + 请求/响应拦截器
│   ├── auth.ts             # 认证（登录、登出、验证码、用户信息、密码）
│   ├── user.ts             # 用户管理 CRUD
│   ├── role.ts             # 角色管理 CRUD + 菜单/权限分配
│   ├── permission.ts       # 权限管理 CRUD + 路由同步
│   ├── menu.ts             # 菜单管理 CRUD + 树形结构
│   ├── log.ts              # 登录日志 + 操作日志
│   ├── dashboard.ts        # 仪表盘统计 + 图表数据
│   ├── notification.ts     # 系统通知管理 + 用户通知
│   └── upload.ts           # 文件上传
├── types/                  # TypeScript 类型定义
│   ├── api.ts              # 所有 API 响应和实体类型（hash_id: string）
│   └── router.ts           # RouteMeta 扩展
├── stores/                 # Pinia 状态管理
│   ├── app.ts              # 应用设置（侧边栏、主题、语言）
│   ├── auth.ts             # 认证状态（Token、用户信息、角色）
│   ├── permission.ts       # 菜单、权限、动态路由
│   └── tagsView.ts         # 标签页导航
├── router/                 # 路由配置
│   ├── index.ts            # 静态路由（Login、Layout、403）
│   └── guard.ts            # 路由守卫（认证、动态路由加载）
├── layout/                 # 布局组件
│   ├── index.vue           # 主布局容器
│   └── components/
│       ├── Sidebar/        # 侧边栏（递归菜单项）
│       ├── Navbar/         # 导航栏（面包屑、搜索、全屏、语言、主题、通知、用户）
│       ├── TagsView/       # 标签导航（右键菜单）
│       └── AppMain.vue     # 页面内容区（keep-alive + 过渡动画）
├── views/                  # 页面视图
│   ├── login/              # 登录页
│   ├── dashboard/          # 仪表盘
│   ├── system/             # 系统管理（用户、角色、权限、菜单）
│   ├── logs/               # 日志管理（登录日志、操作日志）
│   ├── notifications/      # 通知管理
│   ├── profile/            # 个人中心（个人信息、修改密码）
│   └── error/              # 错误页（403、404）
├── composables/            # 组合式函数
│   ├── useTable.ts         # 通用表格逻辑（分页、搜索、选择）
│   └── useForm.ts          # 通用表单逻辑（验证、提交）
├── directives/             # 自定义指令
│   └── permission.ts       # v-permission 按钮权限指令
├── locales/                # 国际化翻译文件
│   ├── index.ts            # i18n 配置
│   ├── zh-CN.ts            # 中文翻译
│   └── en.ts               # 英文翻译
├── styles/                 # 全局样式
│   ├── variables.scss      # CSS 自定义属性（亮色/暗色主题）
│   ├── reset.scss          # CSS 重置
│   ├── sidebar.scss        # 侧边栏样式
│   ├── transition.scss     # 过渡动画
│   ├── element-override.scss  # Element Plus 暗色模式覆写
│   └── index.scss          # 全局入口样式
├── components/             # 公共组件
│   └── Pagination/         # 分页组件
└── utils/                  # 工具函数
    ├── auth.ts             # 权限检查辅助函数
    ├── validate.ts         # 表单验证
    └── format.ts           # 日期、文件大小格式化
```

## 环境要求

- Node.js >= 18
- npm >= 9

## 安装与运行

### 1. 安装依赖

```bash
cd news-rbac-admin
npm install
```

### 2. 启动开发服务器

```bash
npm run dev
```

开发服务器启动在 `http://localhost:3001`，API 请求通过 Vite 代理转发到后端 `http://127.0.0.1:8001`。

### 3. 构建生产版本

```bash
npm run build
```

构建输出在 `dist/` 目录。

### 4. 预览生产构建

```bash
npm run preview
```

## 后端 API 配置

本项目配套后端为 `laravel-rbac-api`，需确保：

- 后端运行在 `http://127.0.0.1:8001`（开发代理配置在 `vite.config.ts`）
- 所有 API 接口前缀为 `/admin/`
- 认证方式为 Laravel Sanctum Bearer Token
- 所有资源 ID 为 `hash_id` 字符串（Hashids 编码，非自增数字）
- API 响应格式：`{ data: ..., message: "...", status_code: 200 }`
- 分页响应格式：`{ data: { list: [...], total: N, current_page: N, per_page: N, last_page: N } }`

## 默认登录账号

请通过后端 Seeder 创建初始管理员账号：

```bash
cd ../laravel-rbac-api
php artisan db:seed --class=AdminPermissionSeeder
```

默认账号：`admin` / `admin123`

## 环境变量

| 变量名 | 说明 | 开发默认值 |
|--------|------|-----------|
| `VITE_APP_TITLE` | 应用标题 | RBAC Admin |
| `VITE_API_BASE_URL` | API 基础地址 | （空，由代理处理） |
| `VITE_UPLOAD_URL` | 上传文件 URL 前缀 | `http://127.0.0.1:8001` |

## 主题切换

支持亮色/暗色两种主题，通过 HTML 元素的 `data-theme` 属性控制：

- `data-theme="light"` — 亮色主题（默认深色侧边栏 + 浅色内容区）
- `data-theme="dark"` — 暗色主题

所有颜色通过 CSS Custom Properties 定义，支持自定义扩展。

## 国际化

支持中文（`zh-CN`）和英文（`en`）两种语言，语言偏好保存在 `localStorage` 中。

切换方式：导航栏右侧语言切换按钮，或代码中调用：

```typescript
import { useAppStore } from '@/stores/app'
const appStore = useAppStore()
appStore.setLanguage('en') // 或 'zh-CN'
```
