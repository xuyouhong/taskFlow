<?php

namespace Database\Seeders;
// 注意：命名空间改为 Laravel 默认的

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Admin\Permission\Models\AdminUser;
use Admin\Permission\Models\AdminRole;
use Admin\Permission\Models\AdminPermission;
use Admin\Permission\Models\AdminMenu;

class AdminPermissionSeeder extends Seeder  // 修改类名
{
    public function run()
    {
        // 清空数据
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        // 检查表是否存在
        $tables = [
            'admin_user_role',
            'admin_role_permission',
            'admin_role_menu',
            'admin_login_logs',
            'admin_operation_logs',
            'admin_users',
            'admin_roles',
            'admin_permissions',
            'admin_menus',
            'admin_notifications',
            'admin_user_notifications',
        ];

        foreach ($tables as $table) {
            if (DB::getSchemaBuilder()->hasTable($table)) {
                DB::table($table)->truncate();
                // 使用 echo 替代 $this->command，避免在某些环境下 $this->command 为 null
                echo "已清空表: {$table}\n";
            } else {
                // 使用 echo 替代 $this->command，避免在某些环境下 $this->command 为 null
                echo "表不存在: {$table}\n";
            }
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // 创建超级管理员角色
        $superAdminRole = AdminRole::create([
            'name'        => '超级管理员',
            'slug'        => 'super-admin',
            'description' => '系统超级管理员，拥有所有权限',
            'status'      => 1,
            'sort'        => 0,
        ]);

        // 创建管理员角色
        $adminRole = AdminRole::create([
            'name'        => '管理员',
            'slug'        => 'admin',
            'description' => '系统管理员',
            'status'      => 1,
            'sort'        => 1,
        ]);

        // 创建普通用户角色
        $userRole = AdminRole::create([
            'name'        => '普通用户',
            'slug'        => 'user',
            'description' => '普通用户',
            'status'      => 1,
            'sort'        => 2,
        ]);

        // 创建超级管理员用户
        $superAdmin = AdminUser::create([
            'username'  => 'super',
            'email'     => 'xuyouhong_@hotmail.com',
            'avatar'    => 'https://oss.eyesnews.cn/dev/upload/image/2025/05/15/dD0xNzQ3Mjk1NzMx.gif',
            'password'  => Hash::make('gzrbbks'),
            'real_name' => '超级管理员',
            'status'    => 1,
        ]);

        // 创建管理员用户
        $admin = AdminUser::create([
            'username'  => 'admin',
            'email'     => 'admin@admin.com',
            'password'  => Hash::make('123456'),
            'real_name' => '管理员',
            'status'    => 1,
        ]);

        // 创建普通用户
        $user = AdminUser::create([
            'username'  => 'user',
            'email'     => 'user@admin.com',
            'password'  => Hash::make('123456'),
            'real_name' => '普通用户',
            'status'    => 1,
        ]);

        // 分配角色
        $superAdmin->roles()->attach($superAdminRole);
        $admin->roles()->attach($adminRole);
        $user->roles()->attach($userRole);

        // 创建权限
        $permissions = [
            // 用户管理权限
            ['name' => '用户列表', 'slug' => 'users.index', 'http_method' => 'GET', 'http_path' => '/admin/users', 'sort' => 10, 'status' => 1],
            ['name' => '创建用户', 'slug' => 'users.store', 'http_method' => 'POST', 'http_path' => '/admin/users', 'sort' => 11, 'status' => 1],
            ['name' => '查看用户', 'slug' => 'users.show', 'http_method' => 'GET', 'http_path' => '/admin/users/*', 'sort' => 12, 'status' => 1],
            ['name' => '更新用户', 'slug' => 'users.update', 'http_method' => 'PUT', 'http_path' => '/admin/users/*', 'sort' => 13, 'status' => 1],
            ['name' => '删除用户', 'slug' => 'users.destroy', 'http_method' => 'DELETE', 'http_path' => '/admin/users/*', 'sort' => 14, 'status' => 1],
            ['name' => '批量更新用户状态', 'slug' => 'users.batch-status', 'http_method' => 'POST', 'http_path' => '/admin/users/batch-status', 'sort' => 15, 'status' => 1],

            // 角色管理权限
            ['name' => '角色列表', 'slug' => 'roles.index', 'http_method' => 'GET', 'http_path' => '/admin/roles', 'sort' => 20, 'status' => 1],
            ['name' => '创建角色', 'slug' => 'roles.store', 'http_method' => 'POST', 'http_path' => '/admin/roles', 'sort' => 21, 'status' => 1],
            ['name' => '查看角色', 'slug' => 'roles.show', 'http_method' => 'GET', 'http_path' => '/admin/roles/*', 'sort' => 22, 'status' => 1],
            ['name' => '更新角色', 'slug' => 'roles.update', 'http_method' => 'PUT', 'http_path' => '/admin/roles/*', 'sort' => 23, 'status' => 1],
            ['name' => '删除角色', 'slug' => 'roles.destroy', 'http_method' => 'DELETE', 'http_path' => '/admin/roles/*', 'sort' => 24, 'status' => 1],
            ['name' => '批量更新角色状态', 'slug' => 'roles.batch-status', 'http_method' => 'POST', 'http_path' => '/admin/roles/batch-status', 'sort' => 25, 'status' => 1],
            ['name' => '分配菜单给角色', 'slug' => 'roles.assign-menus', 'http_method' => 'POST', 'http_path' => '/admin/roles/assign-menus/*', 'sort' => 26, 'status' => 1],
            ['name' => '分配权限给角色', 'slug' => 'roles.assign-permissions', 'http_method' => 'POST', 'http_path' => '/admin/roles/assign-permissions/*', 'sort' => 27, 'status' => 1],

            // 权限管理权限
            ['name' => '权限列表', 'slug' => 'permissions.index', 'http_method' => 'GET', 'http_path' => '/admin/permissions', 'sort' => 30, 'status' => 1],
            ['name' => '创建权限', 'slug' => 'permissions.store', 'http_method' => 'POST', 'http_path' => '/admin/permissions', 'sort' => 31, 'status' => 1],
            ['name' => '查看权限', 'slug' => 'permissions.show', 'http_method' => 'GET', 'http_path' => '/admin/permissions/*', 'sort' => 32, 'status' => 1],
            ['name' => '更新权限', 'slug' => 'permissions.update', 'http_method' => 'PUT', 'http_path' => '/admin/permissions/*', 'sort' => 33, 'status' => 1],
            ['name' => '删除权限', 'slug' => 'permissions.destroy', 'http_method' => 'DELETE', 'http_path' => '/admin/permissions/*', 'sort' => 34, 'status' => 1],
            ['name' => '批量更新权限状态', 'slug' => 'permissions.batch-status', 'http_method' => 'POST', 'http_path' => '/admin/permissions/batch-status', 'sort' => 35, 'status' => 1],
            ['name' => '同步路由权限', 'slug' => 'permissions.sync-routes', 'http_method' => 'POST', 'http_path' => '/admin/permissions/sync-routes', 'sort' => 36, 'status' => 1],

            // 菜单管理权限
            ['name' => '菜单列表', 'slug' => 'menus.index', 'http_method' => 'GET', 'http_path' => '/admin/menus', 'sort' => 40, 'status' => 1],
            ['name' => '创建菜单', 'slug' => 'menus.store', 'http_method' => 'POST', 'http_path' => '/admin/menus', 'sort' => 41, 'status' => 1],
            ['name' => '查看菜单', 'slug' => 'menus.show', 'http_method' => 'GET', 'http_path' => '/admin/menus/*', 'sort' => 42, 'status' => 1],
            ['name' => '更新菜单', 'slug' => 'menus.update', 'http_method' => 'PUT', 'http_path' => '/admin/menus/*', 'sort' => 43, 'status' => 1],
            ['name' => '删除菜单', 'slug' => 'menus.destroy', 'http_method' => 'DELETE', 'http_path' => '/admin/menus/*', 'sort' => 44, 'status' => 1],
            ['name' => '菜单树形结构', 'slug' => 'menus.tree', 'http_method' => 'GET', 'http_path' => '/admin/menus-tree', 'sort' => 45, 'status' => 1],
            ['name' => '用户菜单', 'slug' => 'menus.user-menus', 'http_method' => 'GET', 'http_path' => '/admin/user-menus', 'sort' => 46, 'status' => 1],
            ['name' => '更新菜单排序', 'slug' => 'menus.sort', 'http_method' => 'PUT', 'http_path' => '/admin/menus/sort', 'sort' => 47, 'status' => 1],
            ['name' => '批量更新菜单状态', 'slug' => 'menus.batch-status', 'http_method' => 'POST', 'http_path' => '/admin/menus/batch-status', 'sort' => 48, 'status' => 1],

            // 登录日志权限
            ['name' => '登录日志列表', 'slug' => 'login-logs.index', 'http_method' => 'GET', 'http_path' => '/admin/login-logs', 'sort' => 50, 'status' => 1],
            ['name' => '查看登录日志', 'slug' => 'login-logs.show', 'http_method' => 'GET', 'http_path' => '/admin/login-logs/*', 'sort' => 51, 'status' => 1],
            ['name' => '删除登录日志', 'slug' => 'login-logs.destroy', 'http_method' => 'DELETE', 'http_path' => '/admin/login-logs/*', 'sort' => 52, 'status' => 1],
            ['name' => '批量删除登录日志', 'slug' => 'login-logs.batch-destroy', 'http_method' => 'POST', 'http_path' => '/admin/login-logs/batch-destroy', 'sort' => 53, 'status' => 1],
            ['name' => '登录日志统计', 'slug' => 'login-logs.statistics', 'http_method' => 'GET', 'http_path' => '/admin/login-logs/statistics', 'sort' => 54, 'status' => 1],

            // 操作日志权限
            ['name' => '操作日志列表', 'slug' => 'operation-logs.index', 'http_method' => 'GET', 'http_path' => '/admin/operation-logs', 'sort' => 60, 'status' => 1],
            ['name' => '查看操作日志', 'slug' => 'operation-logs.show', 'http_method' => 'GET', 'http_path' => '/admin/operation-logs/*', 'sort' => 61, 'status' => 1],
            ['name' => '删除操作日志', 'slug' => 'operation-logs.destroy', 'http_method' => 'DELETE', 'http_path' => '/admin/operation-logs/*', 'sort' => 62, 'status' => 1],
            ['name' => '批量删除操作日志', 'slug' => 'operation-logs.batch-destroy', 'http_method' => 'POST', 'http_path' => '/admin/operation-logs/batch-destroy', 'sort' => 63, 'status' => 1],
            ['name' => '清空操作日志', 'slug' => 'operation-logs.clean', 'http_method' => 'POST', 'http_path' => '/admin/operation-logs/clean', 'sort' => 64, 'status' => 1],
            ['name' => '操作日志统计', 'slug' => 'operation-logs.statistics', 'http_method' => 'GET', 'http_path' => '/admin/operation-logs/statistics', 'sort' => 65, 'status' => 1],

            // 管理员通知权限
            ['name' => '获取通知列表', 'slug' => 'notifications.index', 'http_method' => 'GET', 'http_path' => '/admin/notifications', 'sort' => 90, 'status' => 1],
            ['name' => '创建通知', 'slug' => 'notifications.store', 'http_method' => 'POST', 'http_path' => '/admin/notifications', 'sort' => 91, 'status' => 1],
            ['name' => '获取通知详情', 'slug' => 'notifications.show', 'http_method' => 'GET', 'http_path' => '/admin/notifications/*', 'sort' => 92, 'status' => 1],
            ['name' => '更新通知', 'slug' => 'notifications.update', 'http_method' => 'PUT', 'http_path' => '/admin/notifications/*', 'sort' => 93, 'status' => 1],
            ['name' => '删除通知', 'slug' => 'notifications.destroy', 'http_method' => 'DELETE', 'http_path' => '/admin/notifications/*', 'sort' => 94, 'status' => 1],
            ['name' => '发布通知', 'slug' => 'notifications.publish', 'http_method' => 'POST', 'http_path' => '/admin/notifications/*/publish', 'sort' => 95, 'status' => 1],
            ['name' => '撤销通知', 'slug' => 'notifications.revoke', 'http_method' => 'POST', 'http_path' => '/admin/notifications/*/revoke', 'sort' => 96, 'status' => 1],
        ];

        foreach ($permissions as $permission) {
            AdminPermission::create($permission);
        }

        // 为超级管理员角色分配所有权限
        $allPermissions = AdminPermission::pluck('hash_id');
        $superAdminRole->permissions()->sync($allPermissions);

        // ============================
        // 创建菜单（使用变量引用 parent_id）
        // ============================

        // 根菜单：首页
        $dashboardMenu = AdminMenu::create([
            'parent_id'   => null,
            'name'        => '首页',
            'icon'        => 'monitor',
            'path'        => '/dashboard',
            'component'   => 'Layout',
            'sort'        => 1,
            'type'        => AdminMenu::TYPE_MENU,
            'status'      => 1,
            'is_link'     => 0,
            'keep_alive'  => 1,
            'description' => '系统首页',
        ]);

        // 根菜单：系统管理
        $systemMenu = AdminMenu::create([
            'parent_id'   => null,
            'name'        => '系统管理',
            'icon'        => 'setting',
            'path'        => '/system',
            'component'   => 'Layout',
            'sort'        => 100,
            'type'        => AdminMenu::TYPE_DIRECTORY,
            'status'      => 1,
            'is_link'     => 0,
            'keep_alive'  => 1,
            'description' => '系统管理',
        ]);

        // 子菜单：用户管理（父：系统管理）
        AdminMenu::create([
            'parent_id'   => $systemMenu->hash_id,
            'name'        => '用户管理',
            'icon'        => 'user',
            'path'        => '/system/users',
            'component'   => 'system/users/index',
            'sort'        => 101,
            'type'        => AdminMenu::TYPE_MENU,
            'status'      => 1,
            'is_link'     => 0,
            'keep_alive'  => 1,
            'description' => '用户管理',
        ]);

        // 子菜单：角色管理（父：系统管理）
        AdminMenu::create([
            'parent_id'   => $systemMenu->hash_id,
            'name'        => '角色管理',
            'icon'        => 'avatar',
            'path'        => '/system/roles',
            'component'   => 'system/roles/index',
            'sort'        => 102,
            'type'        => AdminMenu::TYPE_MENU,
            'status'      => 1,
            'is_link'     => 0,
            'keep_alive'  => 1,
            'description' => '角色管理',
        ]);

        // 子菜单：权限管理（父：系统管理）
        AdminMenu::create([
            'parent_id'   => $systemMenu->hash_id,
            'name'        => '权限管理',
            'icon'        => 'lock',
            'path'        => '/system/permissions',
            'component'   => 'system/permissions/index',
            'sort'        => 103,
            'type'        => AdminMenu::TYPE_MENU,
            'status'      => 1,
            'is_link'     => 0,
            'keep_alive'  => 1,
            'description' => '权限管理',
        ]);

        // 子菜单：菜单管理（父：系统管理）
        AdminMenu::create([
            'parent_id'   => $systemMenu->hash_id,
            'name'        => '菜单管理',
            'icon'        => 'menu',
            'path'        => '/system/menus',
            'component'   => 'system/menus/index',
            'sort'        => 104,
            'type'        => AdminMenu::TYPE_MENU,
            'status'      => 1,
            'is_link'     => 0,
            'keep_alive'  => 1,
            'description' => '菜单管理',
        ]);

        // 根菜单：日志管理
        $logMenu = AdminMenu::create([
            'parent_id'   => null,
            'name'        => '日志管理',
            'icon'        => 'document',
            'path'        => '/logs',
            'component'   => 'Layout',
            'sort'        => 200,
            'type'        => AdminMenu::TYPE_DIRECTORY,
            'status'      => 1,
            'is_link'     => 0,
            'keep_alive'  => 1,
            'description' => '日志管理',
        ]);

        // 子菜单：登录日志（父：日志管理）
        AdminMenu::create([
            'parent_id'   => $logMenu->hash_id,
            'name'        => '登录日志',
            'icon'        => 'memo',
            'path'        => '/logs/login-logs',
            'component'   => 'logs/login-logs/index',
            'sort'        => 201,
            'type'        => AdminMenu::TYPE_MENU,
            'status'      => 1,
            'is_link'     => 0,
            'keep_alive'  => 1,
            'description' => '登录日志',
        ]);

        // 子菜单：操作日志（父：日志管理）
        AdminMenu::create([
            'parent_id'   => $logMenu->hash_id,
            'name'        => '操作日志',
            'icon'        => 'tickets',
            'path'        => '/logs/operation-logs',
            'component'   => 'logs/operation-logs/index',
            'sort'        => 202,
            'type'        => AdminMenu::TYPE_MENU,
            'status'      => 1,
            'is_link'     => 0,
            'keep_alive'  => 1,
            'description' => '操作日志',
        ]);

        // 根菜单：通知通告
        $notifMenu = AdminMenu::create([
            'parent_id'   => null,
            'name'        => '通知通告',
            'icon'        => 'bell',
            'path'        => '/notifications',
            'component'   => 'Layout',
            'sort'        => 300,
            'type'        => AdminMenu::TYPE_DIRECTORY,
            'status'      => 1,
            'is_link'     => 0,
            'keep_alive'  => 1,
            'description' => '通知通告管理',
        ]);

        // 子菜单：通知管理（父：通知通告）
        AdminMenu::create([
            'parent_id'   => $notifMenu->hash_id,
            'name'        => '通知管理',
            'icon'        => 'message',
            'path'        => '/notifications/index',
            'component'   => 'notifications/index',
            'sort'        => 301,
            'type'        => AdminMenu::TYPE_MENU,
            'status'      => 1,
            'is_link'     => 0,
            'keep_alive'  => 1,
            'description' => '通知管理',
        ]);

        // 为超级管理员角色分配所有菜单
        $allMenus = AdminMenu::pluck('hash_id');
        $superAdminRole->menus()->sync($allMenus);

        // 为管理员和普通用户角色分配首页菜单
        $adminRole->menus()->sync([$dashboardMenu->hash_id]);
        $userRole->menus()->sync([$dashboardMenu->hash_id]);

        echo "✅ 权限数据填充成功！\n";
        echo "超级管理员账号: super / gzrbbks\n";
        echo "管理员账号: admin / 123456\n";
        echo "普通用户账号: user / 123456\n";
    }
}
