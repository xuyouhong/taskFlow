<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Node;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AgentController extends BaseController
{
    public function register(Request $request): JsonResponse
    {
        $request->validate([
            'hostname' => 'required|string|max:100',
            'ip' => 'required|ip',
            'agent_port' => 'required|integer|min:1|max:65535',
            'cpu_cores' => 'nullable|integer|min:1',
            'memory_total_mb' => 'nullable|integer|min:1',
            'agent_version' => 'nullable|string|max:20',
        ]);

        $token = $request->header('X-Agent-Token');
        if (!$token) {
            return $this->error('Agent Token缺失', 3001);
        }

        $node = Node::where('agent_token', $token)->first();
        if (!$node) {
            return $this->error('Agent Token无效', 3001);
        }

        $node->update([
            'hostname' => $request->hostname,
            'ip' => $request->ip,
            'agent_port' => $request->agent_port,
            'cpu_cores' => $request->cpu_cores,
            'memory_total_mb' => $request->memory_total_mb,
            'agent_version' => $request->agent_version,
            'status' => 'online',
            'last_heartbeat_at' => now(),
        ]);

        return $this->success(['node_id' => $node->hash_id]);
    }

    public function heartbeat(Request $request): JsonResponse
    {
        $request->validate([
            'node_id' => 'required|string',
            'cpu_usage' => 'nullable|numeric|min:0|max:100',
            'memory_usage' => 'nullable|numeric|min:0|max:100',
            'running_tasks' => 'nullable|integer|min:0',
        ]);

        $token = $request->header('X-Agent-Token');
        $node = Node::where('agent_token', $token)
            ->where('hash_id', $request->node_id)
            ->first();

        if (!$node) {
            return $this->error('节点不存在或Token无效', 3001);
        }

        $node->update([
            'status' => 'online',
            'last_heartbeat_at' => now(),
        ]);

        return $this->success(['timestamp' => now()->timestamp]);
    }

    public function callback(Request $request): JsonResponse
    {
        $request->validate([
            'execution_id' => 'required|uuid',
            'exit_code' => 'nullable|integer',
            'status' => 'required|in:success,failed,timeout,rejected',
            'stdout' => 'nullable|string',
            'stderr' => 'nullable|string',
            'duration_ms' => 'nullable|integer|min:0',
        ]);

        $signature = $request->header('X-Signature');
        $timestamp = $request->header('X-Timestamp');

        if (!$signature || !$timestamp) {
            return $this->error('签名信息缺失', 3001);
        }

        // 验证时间戳偏差
        if (abs(time() - (int)$timestamp) > 60) {
            return $this->error('时间戳偏差过大', 3002);
        }

        // TODO: 查找对应的task_log并更新状态
        // 这里需要通过execution_id查找TaskLog并更新

        return $this->success(['received' => true]);
    }
}
