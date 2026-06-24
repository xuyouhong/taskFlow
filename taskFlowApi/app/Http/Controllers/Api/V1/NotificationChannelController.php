<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\NotificationChannel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationChannelController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = NotificationChannel::query();

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $channels = $query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 15));

        return $this->success($channels);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:50',
            'type' => 'required|in:email,webhook,dingtalk,wecom,feishu',
            'config' => 'required|array',
            'status' => 'nullable|integer|in:0,1',
        ]);

        $channel = NotificationChannel::create([
            'name' => $request->name,
            'type' => $request->type,
            'config' => $request->config,
            'status' => $request->status ?? 1,
        ]);

        return $this->success($channel, '创建成功');
    }

    public function show(string $hashId): JsonResponse
    {
        $channel = NotificationChannel::find($hashId);
        if (!$channel) {
            return $this->error('渠道不存在', 1002);
        }
        return $this->success($channel);
    }

    public function update(Request $request, string $hashId): JsonResponse
    {
        $channel = NotificationChannel::find($hashId);
        if (!$channel) {
            return $this->error('渠道不存在', 1002);
        }

        $request->validate([
            'name' => 'nullable|string|max:50',
            'type' => 'nullable|in:email,webhook,dingtalk,wecom,feishu',
            'config' => 'nullable|array',
            'status' => 'nullable|integer|in:0,1',
        ]);

        $channel->fill($request->only(['name', 'type', 'config', 'status']));
        $channel->save();

        return $this->success($channel, '更新成功');
    }

    public function destroy(string $hashId): JsonResponse
    {
        $channel = NotificationChannel::find($hashId);
        if (!$channel) {
            return $this->error('渠道不存在', 1002);
        }

        $channel->delete();
        return $this->success(null, '删除成功');
    }
}
