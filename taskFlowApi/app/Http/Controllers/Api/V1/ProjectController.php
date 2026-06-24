<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends BaseController
{
    public function index(Request $request): JsonResponse
    {
        $query = Project::query();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('keyword')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->keyword}%")
                    ->orWhere('code', 'like', "%{$request->keyword}%");
            });
        }

        $projects = $query->orderBy('created_at', 'desc')->paginate($request->get('per_page', 15));

        return $this->success($projects);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:50',
            'code' => 'required|string|max:50|unique:projects,code',
            'description' => 'nullable|string',
            'owner_id' => 'required|string',
            'status' => 'nullable|integer|in:0,1',
        ]);

        $project = Project::create([
            'name' => $request->name,
            'code' => $request->code,
            'description' => $request->description,
            'owner_id' => $request->owner_id,
            'status' => $request->status ?? 1,
        ]);

        return $this->success($project, '创建成功');
    }

    public function show(string $hashId): JsonResponse
    {
        $project = Project::with(['owner', 'users'])->find($hashId);
        if (!$project) {
            return $this->error('项目不存在', 1002);
        }
        return $this->success($project);
    }

    public function update(Request $request, string $hashId): JsonResponse
    {
        $project = Project::find($hashId);
        if (!$project) {
            return $this->error('项目不存在', 1002);
        }

        $request->validate([
            'name' => 'nullable|string|max:50',
            'code' => 'nullable|string|max:50|unique:projects,code,' . $hashId . ',hash_id',
            'description' => 'nullable|string',
            'owner_id' => 'nullable|string',
            'status' => 'nullable|integer|in:0,1',
        ]);

        $project->fill($request->only(['name', 'code', 'description', 'owner_id', 'status']));
        $project->save();

        return $this->success($project, '更新成功');
    }

    public function destroy(string $hashId): JsonResponse
    {
        $project = Project::find($hashId);
        if (!$project) {
            return $this->error('项目不存在', 1002);
        }

        $project->delete();
        return $this->success(null, '删除成功');
    }

    public function members(Request $request, string $hashId): JsonResponse
    {
        $project = Project::with(['projectUsers.user'])->find($hashId);
        if (!$project) {
            return $this->error('项目不存在', 1002);
        }
        return $this->success($project->projectUsers);
    }

    public function addMember(Request $request, string $hashId): JsonResponse
    {
        $project = Project::find($hashId);
        if (!$project) {
            return $this->error('项目不存在', 1002);
        }

        $request->validate([
            'user_id' => 'required|string',
            'role' => 'required|in:owner,member,viewer',
        ]);

        $project->users()->attach($request->user_id, ['role' => $request->role]);

        return $this->success(null, '添加成功');
    }

    public function removeMember(Request $request, string $hashId, string $userId): JsonResponse
    {
        $project = Project::find($hashId);
        if (!$project) {
            return $this->error('项目不存在', 1002);
        }

        $project->users()->detach($userId);
        return $this->success(null, '移除成功');
    }
}
