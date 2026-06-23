<?php

namespace Admin\Permission\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Admin\Permission\Models\AdminUserNotification;

class AdminUserNotificationController extends AdminController
{
    public function index(Request $request): JsonResponse
    {
        $user  = $request->user();
        $query = AdminUserNotification::where('user_id', $user->hash_id)
            ->with(['notification', 'notification.sender']);

        if ($request->has('is_read')) {
            $query->where('is_read', $request->is_read);
        }

        if ($request->has('type')) {
            $query->whereHas('notification', function ($q) use ($request) {
                $q->where('type', $request->type);
            });
        }

        $query->orderBy('_seq', 'desc');
        $notifications = $query->paginate($request->get('per_page', 15));

        return $this->paginate($notifications);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $user  = $request->user();
        $count = AdminUserNotification::where('user_id', $user->hash_id)
            ->where('is_read', 0)
            ->count();

        return $this->success(['count' => $count]);
    }

    public function markAsRead($hashId): JsonResponse
    {
        $notification = AdminUserNotification::where('user_id', request()->user()->hash_id)
            ->findOrFail($hashId);

        $notification->markAsRead();

        return $this->success([], '已标记为已读');
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $user  = $request->user();
        $count = AdminUserNotification::where('user_id', $user->hash_id)
            ->where('is_read', 0)
            ->update([
                'is_read' => 1,
                'read_at' => now()
            ]);

        return $this->success(['count' => $count], '所有通知已标记为已读');
    }

    public function show($hashId): JsonResponse
    {
        $notification = AdminUserNotification::where('user_id', request()->user()->hash_id)
            ->with(['notification', 'notification.sender'])
            ->findOrFail($hashId);

        $notification->markAsRead();

        return $this->success($notification->toArray());
    }

    public function destroy($hashId): JsonResponse
    {
        $notification = AdminUserNotification::where('user_id', request()->user()->hash_id)
            ->findOrFail($hashId);

        $notification->delete();

        return $this->success([], '通知已删除');
    }

    public function batchDestroy(Request $request): JsonResponse
    {
        $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'string'
        ]);

        $user  = $request->user();
        $count = AdminUserNotification::where('user_id', $user->hash_id)
            ->whereIn('hash_id', $request->ids)
            ->delete();

        return $this->success(['count' => $count], '通知已批量删除');
    }
}
