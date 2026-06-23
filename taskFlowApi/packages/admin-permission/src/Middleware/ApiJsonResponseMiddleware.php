<?php

namespace Admin\Permission\Middleware;

use Closure;
use Illuminate\Http\Request;

class ApiJsonResponseMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // 强制所有请求返回 JSON 响应
        $request->headers->set('Accept', 'application/json');

        $response = $next($request);

        // 确保响应头设置正确
        if (!$response->headers->has('Content-Type')) {
            $response->header('Content-Type', 'application/json');
        }

        // 如果响应是未认证错误且非 JSON 格式，转换为统一 JSON 格式
        if ($response->getStatusCode() === 401 &&
            !str_contains($response->headers->get('Content-Type', ''), 'json')) {
            return response()->json([
                'status_code' => 401,
                'message'     => '未登录或登录已过期',
                'data'        => null
            ], 401);
        }

        return $response;
    }
}
