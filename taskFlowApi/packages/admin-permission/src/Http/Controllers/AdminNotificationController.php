<?php

namespace Admin\Permission\Http\Controllers;

use Illuminate\Http\Request;
use Admin\Permission\Models\AdminNotification;
use Admin\Permission\Models\AdminUser;
use Admin\Permission\Models\AdminRole;
use Admin\Permission\Models\AdminUserNotification;
use Admin\Permission\Http\Requests\AdminNotificationRequest;

class AdminNotificationController extends AdminController
{
    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        $query = AdminNotification::with('sender');

        if ($request->has('title') && $request->title) {
            $query->where('title', 'like', '%' . $request->title . '%');
        }

        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('priority') && $request->priority) {
            $query->where('priority', $request->priority);
        }

        if ($request->has('sender_id') && $request->sender_id) {
            $query->where('sender_id', $request->sender_id);
        }

        $notifications = $query->orderBy('_seq', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->paginate($notifications);
    }

    public function show($hashId): \Illuminate\Http\JsonResponse
    {
        $notification = AdminNotification::findOrFail($hashId);
        $notification->load('sender');
        return $this->success($notification->toArray());
    }

    public function store(AdminNotificationRequest $request): \Illuminate\Http\JsonResponse
    {
        $data              = $request->validated();
        $data['sender_id'] = $request->user()->hash_id;

        if ($data['status'] == 2 && empty($data['publish_time'])) {
            $data['publish_time'] = now();
        }

        // target_values 直接存 hash_id 数组（前端传来的就是 hash_id）
        $notification = AdminNotification::create($data);

        if ($data['status'] == 2) {
            $this->createUserNotifications($notification);
        }

        return $this->success($notification->toArray(), '通知创建成功');
    }

    public function update(AdminNotificationRequest $request, $hashId): \Illuminate\Http\JsonResponse
    {
        $notification = AdminNotification::findOrFail($hashId);
        $oldStatus    = $notification->status;
        $data         = $request->validated();

        if ($oldStatus != 2 && $data['status'] == 2 && empty($data['publish_time'])) {
            $data['publish_time'] = now();
        }

        // target_values 直接存 hash_id 数组
        $notification->update($data);

        if ($oldStatus != 2 && $data['status'] == 2) {
            $this->createUserNotifications($notification);
        } else if ($oldStatus == 2 && $data['status'] == 3) {
            $notification->userNotifications()->delete();
        } else if ($oldStatus == 2 && $data['status'] == 2) {
            $notification->userNotifications()->delete();
            $this->createUserNotifications($notification);
        }

        return $this->success($notification->toArray(), '通知更新成功');
    }

    public function destroy($hashId): \Illuminate\Http\JsonResponse
    {
        $notification = AdminNotification::findOrFail($hashId);
        $notification->userNotifications()->delete();
        $notification->delete();

        return $this->success([], '通知删除成功');
    }

    public function publish($hashId): \Illuminate\Http\JsonResponse
    {
        $notification = AdminNotification::findOrFail($hashId);

        if ($notification->status == 2) {
            return $this->error('只有未发布状态的通知才能发布');
        }

        $notification->update([
            'status'       => 2,
            'publish_time' => now()
        ]);

        $this->createUserNotifications($notification);

        return $this->success($notification->toArray(), '通知发布成功');
    }

    public function revoke($hashId): \Illuminate\Http\JsonResponse
    {
        $notification = AdminNotification::findOrFail($hashId);

        if ($notification->status != 2) {
            return $this->error('只有已发布状态的通知才能撤销');
        }

        $notification->update([
            'status' => 3
        ]);

        $notification->userNotifications()->delete();

        return $this->success($notification->toArray(), '通知撤销成功');
    }

    private function createUserNotifications(AdminNotification $notification): void
    {
        $userIds = [];

        switch ($notification->target_type) {
            case 1: // 所有用户
                $userIds = AdminUser::whereNull('deleted_at')->pluck('hash_id')->toArray();
                break;

            case 2: // 指定角色
                if (!empty($notification->target_values)) {
                    $userIds = \DB::table('admin_user_role')
                        ->whereIn('role_id', (array)$notification->target_values)
                        ->distinct()
                        ->pluck('user_id')
                        ->toArray();
                }
                break;

            case 3: // 指定用户
                $userIds = (array)($notification->target_values ?? []);
                break;
        }

        foreach ($userIds as $userId) {
            AdminUserNotification::updateOrCreate(
                [
                    'user_id'         => $userId,
                    'notification_id' => $notification->hash_id
                ],
                [
                    'is_read' => 0,
                    'read_at' => null
                ]
            );
        }
    }
}
