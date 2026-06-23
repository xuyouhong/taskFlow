<?php

namespace Admin\Permission\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'status_code' => 401,
                'message'     => '未登录',
                'data'        => null
            ], 401);
        }

        // 超级管理员跳过权限检查
        if ($user->hasRole(config('permission.super_admin_role', 'super-admin'))) {
            return $next($request);
        }

        $route      = $request->route();
        $permission = $route->getName();

        if (!$permission) {
            return $next($request);
        }

        if (!$user->hasPermission($permission)) {
            return response()->json([
                'status_code' => 403,
                'message'     => '无权访问',
                'data'        => null
            ], 403);
        }

        return $next($request);
    }
}
