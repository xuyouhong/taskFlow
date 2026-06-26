<?php

namespace Admin\Permission\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Admin\Permission\Models\AdminOperationLog;

class OperationLogMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        $response = $next($request);

        $endTime  = microtime(true);
        $duration = round(($endTime - $startTime) * 1000);

        $user = $request->user();

        if ($user && $this->shouldLogOperation($request)) {
            try {
                AdminOperationLog::create([
                    'user_id'     => $user->hash_id,
                    'username'    => $user->username,
                    'method'      => $request->method(),
                    'path'        => $request->path(),
                    'params'      => $this->getRequestParams($request),
                    'response'    => $this->getResponseContent($response),
                    'ip'          => $request->ip(),
                    'user_agent'  => $request->userAgent(),
                    'status_code' => $response->getStatusCode(),
                    'duration'    => $duration,
                    'operated_at' => now(),
                ]);
            } catch (\Exception $e) {
                // 记录日志失败不中断请求
                \Log::error('操作日志记录失败: ' . $e->getMessage());
            }
        }

        return $response;
    }

    protected function shouldLogOperation(Request $request): bool
    {
        if (!config('permission.logs.enable_operation_log', true)) {
            return false;
        }

        $except = config('permission.logs.except_operation_paths', [
            'logs*',
            'captcha*',
        ]);

        foreach ($except as $pattern) {
            if (Str::is($pattern, $request->path())) {
                return false;
            }
        }

        return in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE']);
    }

    protected function getRequestParams(Request $request): array
    {
        $params = $request->all();

        // 过滤敏感信息
        $sensitiveFields = ['password', 'password_confirmation', 'token', 'access_token'];
        foreach ($sensitiveFields as $field) {
            if (isset($params[$field])) {
                $params[$field] = '***';
            }
        }

        return $params;
    }

    protected function getResponseContent(Response $response)
    {
        $content = $response->getContent();

        if (!is_string($content)) {
            return $content;
        }

        try {
            $decoded = json_decode($content, true);
            $data    = $decoded ?? $content;
        } catch (\Exception $e) {
            $data = $content;
        }

        $serialized = is_string($data) ? $data : json_encode($data, JSON_UNESCAPED_UNICODE);
        if (strlen($serialized) > 1000) {
            return substr($serialized, 0, 1000) . '...(truncated)';
        }

        return $data;
    }
}
