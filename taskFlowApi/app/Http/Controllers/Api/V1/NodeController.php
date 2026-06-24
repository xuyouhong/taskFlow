<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Node;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NodeController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = Node::query();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('keyword')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->keyword}%")
                    ->orWhere('ip', 'like', "%{$request->keyword}%");
            });
        }

        $nodes = $query->orderBy('status', 'desc')
            ->orderBy('last_heartbeat_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->success($nodes);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:50',
            'ip' => 'required|ip',
            'agent_port' => 'nullable|integer|min:1|max:65535',
            'hostname' => 'nullable|string|max:100',
            'agent_token' => 'required|string|max:64',
            'allowed_command_prefix' => 'nullable|string|max:255',
        ]);

        $node = Node::create([
            'name' => $request->name,
            'ip' => $request->ip,
            'agent_port' => $request->agent_port ?? 9501,
            'hostname' => $request->hostname,
            'agent_token' => $request->agent_token,
            'allowed_command_prefix' => $request->allowed_command_prefix,
            'status' => 'offline',
        ]);

        return $this->success($node, '创建成功');
    }

    public function show(string $hashId): JsonResponse
    {
        $node = Node::find($hashId);
        if (!$node) {
            return $this->error('节点不存在', 1002);
        }
        return $this->success($node);
    }

    public function update(Request $request, string $hashId): JsonResponse
    {
        $node = Node::find($hashId);
        if (!$node) {
            return $this->error('节点不存在', 1002);
        }

        $request->validate([
            'name' => 'nullable|string|max:50',
            'ip' => 'nullable|ip',
            'agent_port' => 'nullable|integer|min:1|max:65535',
            'hostname' => 'nullable|string|max:100',
            'allowed_command_prefix' => 'nullable|string|max:255',
        ]);

        $node->fill($request->only([
            'name', 'ip', 'agent_port', 'hostname', 'allowed_command_prefix'
        ]));
        $node->save();

        return $this->success($node, '更新成功');
    }

    public function destroy(string $hashId): JsonResponse
    {
        $node = Node::find($hashId);
        if (!$node) {
            return $this->error('节点不存在', 1002);
        }

        $node->delete();
        return $this->success(null, '删除成功');
    }
}
