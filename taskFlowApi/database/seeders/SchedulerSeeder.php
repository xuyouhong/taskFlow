<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Admin\Permission\Models\AdminMenu;
use Admin\Permission\Models\AdminPermission;

class SchedulerSeeder extends Seeder
{
    public function run()
    {
        // ============================
        // 创建任务调度权限
        // ============================
        $permissions = [
            // 项目管理
            ['name' => '项目列表', 'slug' => 'projects.index', 'http_method' => 'GET', 'http_path' => '/admin/projects', 'sort' => 100, 'status' => 1],
            ['name' => '创建项目', 'slug' => 'projects.store', 'http_method' => 'POST', 'http_path' => '/admin/projects', 'sort' => 101, 'status' => 1],
            ['name' => '查看项目', 'slug' => 'projects.show', 'http_method' => 'GET', 'http_path' => '/admin/projects/*', 'sort' => 102, 'status' => 1],
            ['name' => '更新项目', 'slug' => 'projects.update', 'http_method' => 'PUT', 'http_path' => '/admin/projects/*', 'sort' => 103, 'status' => 1],
            ['name' => '删除项目', 'slug' => 'projects.destroy', 'http_method' => 'DELETE', 'http_path' => '/admin/projects/*', 'sort' => 104, 'status' => 1],
            ['name' => '项目成员列表', 'slug' => 'projects.members', 'http_method' => 'GET', 'http_path' => '/admin/projects/*/members', 'sort' => 105, 'status' => 1],
            ['name' => '添加项目成员', 'slug' => 'projects.add-member', 'http_method' => 'POST', 'http_path' => '/admin/projects/*/members', 'sort' => 106, 'status' => 1],
            ['name' => '移除项目成员', 'slug' => 'projects.remove-member', 'http_method' => 'DELETE', 'http_path' => '/admin/projects/*/members/*', 'sort' => 107, 'status' => 1],

            // 任务管理
            ['name' => '任务列表', 'slug' => 'tasks.index', 'http_method' => 'GET', 'http_path' => '/admin/tasks', 'sort' => 110, 'status' => 1],
            ['name' => '创建任务', 'slug' => 'tasks.store', 'http_method' => 'POST', 'http_path' => '/admin/tasks', 'sort' => 111, 'status' => 1],
            ['name' => '查看任务', 'slug' => 'tasks.show', 'http_method' => 'GET', 'http_path' => '/admin/tasks/*', 'sort' => 112, 'status' => 1],
            ['name' => '更新任务', 'slug' => 'tasks.update', 'http_method' => 'PUT', 'http_path' => '/admin/tasks/*', 'sort' => 113, 'status' => 1],
            ['name' => '删除任务', 'slug' => 'tasks.destroy', 'http_method' => 'DELETE', 'http_path' => '/admin/tasks/*', 'sort' => 114, 'status' => 1],
            ['name' => '手动触发任务', 'slug' => 'tasks.trigger', 'http_method' => 'POST', 'http_path' => '/admin/tasks/*/trigger', 'sort' => 115, 'status' => 1],
            ['name' => '暂停任务', 'slug' => 'tasks.pause', 'http_method' => 'POST', 'http_path' => '/admin/tasks/*/pause', 'sort' => 116, 'status' => 1],
            ['name' => '恢复任务', 'slug' => 'tasks.resume', 'http_method' => 'POST', 'http_path' => '/admin/tasks/*/resume', 'sort' => 117, 'status' => 1],
            ['name' => '任务执行日志', 'slug' => 'tasks.logs', 'http_method' => 'GET', 'http_path' => '/admin/tasks/*/logs', 'sort' => 118, 'status' => 1],

            // 执行日志
            ['name' => '执行日志列表', 'slug' => 'logs.index', 'http_method' => 'GET', 'http_path' => '/admin/logs', 'sort' => 120, 'status' => 1],
            ['name' => '查看执行日志', 'slug' => 'logs.show', 'http_method' => 'GET', 'http_path' => '/admin/logs/*', 'sort' => 121, 'status' => 1],
            ['name' => '归档执行日志', 'slug' => 'logs.archive', 'http_method' => 'GET', 'http_path' => '/admin/logs/archive', 'sort' => 122, 'status' => 1],

            // 节点管理
            ['name' => '节点列表', 'slug' => 'nodes.index', 'http_method' => 'GET', 'http_path' => '/admin/nodes', 'sort' => 130, 'status' => 1],
            ['name' => '创建节点', 'slug' => 'nodes.store', 'http_method' => 'POST', 'http_path' => '/admin/nodes', 'sort' => 131, 'status' => 1],
            ['name' => '查看节点', 'slug' => 'nodes.show', 'http_method' => 'GET', 'http_path' => '/admin/nodes/*', 'sort' => 132, 'status' => 1],
            ['name' => '更新节点', 'slug' => 'nodes.update', 'http_method' => 'PUT', 'http_path' => '/admin/nodes/*', 'sort' => 133, 'status' => 1],
            ['name' => '删除节点', 'slug' => 'nodes.destroy', 'http_method' => 'DELETE', 'http_path' => '/admin/nodes/*', 'sort' => 134, 'status' => 1],

            // 通知渠道管理
            ['name' => '通知渠道列表', 'slug' => 'notification-channels.index', 'http_method' => 'GET', 'http_path' => '/admin/notification-channels', 'sort' => 140, 'status' => 1],
            ['name' => '创建通知渠道', 'slug' => 'notification-channels.store', 'http_method' => 'POST', 'http_path' => '/admin/notification-channels', 'sort' => 141, 'status' => 1],
            ['name' => '查看通知渠道', 'slug' => 'notification-channels.show', 'http_method' => 'GET', 'http_path' => '/admin/notification-channels/*', 'sort' => 142, 'status' => 1],
            ['name' => '更新通知渠道', 'slug' => 'notification-channels.update', 'http_method' => 'PUT', 'http_path' => '/admin/notification-channels/*', 'sort' => 143, 'status' => 1],
            ['name' => '删除通知渠道', 'slug' => 'notification-channels.destroy', 'http_method' => 'DELETE', 'http_path' => '/admin/notification-channels/*', 'sort' => 144, 'status' => 1],
        ];

        foreach ($permissions as $permission) {
            AdminPermission::create($permission);
        }

        // ============================
        // 创建任务调度菜单
        // ============================

        // 根菜单：任务调度
        $schedulerMenu = AdminMenu::create([
            'parent_id'   => null,
            'name'        => '任务调度',
            'icon'        => 'timer',
            'path'        => '/scheduler',
            'component'   => 'Layout',
            'sort'        => 50,
            'type'        => AdminMenu::TYPE_DIRECTORY,
            'status'      => 1,
            'is_link'     => 0,
            'keep_alive'  => 1,
            'description' => '任务调度管理',
        ]);

        // 子菜单：项目管理
        AdminMenu::create([
            'parent_id'   => $schedulerMenu->hash_id,
            'name'        => '项目管理',
            'icon'        => 'folder-opened',
            'path'        => '/scheduler/projects',
            'component'   => 'scheduler/projects/index',
            'sort'        => 51,
            'type'        => AdminMenu::TYPE_MENU,
            'status'      => 1,
            'is_link'     => 0,
            'keep_alive'  => 1,
            'description' => '项目管理',
        ]);

        // 子菜单：任务管理
        AdminMenu::create([
            'parent_id'   => $schedulerMenu->hash_id,
            'name'        => '任务管理',
            'icon'        => 'list',
            'path'        => '/scheduler/tasks',
            'component'   => 'scheduler/tasks/index',
            'sort'        => 52,
            'type'        => AdminMenu::TYPE_MENU,
            'status'      => 1,
            'is_link'     => 0,
            'keep_alive'  => 1,
            'description' => '任务管理',
        ]);

        // 子菜单：执行日志
        AdminMenu::create([
            'parent_id'   => $schedulerMenu->hash_id,
            'name'        => '执行日志',
            'icon'        => 'document',
            'path'        => '/scheduler/task-logs',
            'component'   => 'scheduler/task-logs/index',
            'sort'        => 53,
            'type'        => AdminMenu::TYPE_MENU,
            'status'      => 1,
            'is_link'     => 0,
            'keep_alive'  => 1,
            'description' => '执行日志',
        ]);

        // 子菜单：节点管理
        AdminMenu::create([
            'parent_id'   => $schedulerMenu->hash_id,
            'name'        => '节点管理',
            'icon'        => 'cpu',
            'path'        => '/scheduler/nodes',
            'component'   => 'scheduler/nodes/index',
            'sort'        => 54,
            'type'        => AdminMenu::TYPE_MENU,
            'status'      => 1,
            'is_link'     => 0,
            'keep_alive'  => 1,
            'description' => '节点管理',
        ]);

        // 子菜单：通知渠道
        AdminMenu::create([
            'parent_id'   => $schedulerMenu->hash_id,
            'name'        => '通知渠道',
            'icon'        => 'notification',
            'path'        => '/scheduler/notification-channels',
            'component'   => 'scheduler/notification-channels/index',
            'sort'        => 55,
            'type'        => AdminMenu::TYPE_MENU,
            'status'      => 1,
            'is_link'     => 0,
            'keep_alive'  => 1,
            'description' => '通知渠道',
        ]);

        // 为超级管理员角色分配所有任务调度权限
        $schedulerPermissions = AdminPermission::whereIn('slug', array_column($permissions, 'slug'))->pluck('hash_id');
        $superAdminRole = \Admin\Permission\Models\AdminRole::where('slug', 'super-admin')->first();
        if ($superAdminRole) {
            $superAdminRole->permissions()->syncWithoutDetaching($schedulerPermissions);
        }

        echo "✅ 任务调度数据填充成功！\n";
    }
}
