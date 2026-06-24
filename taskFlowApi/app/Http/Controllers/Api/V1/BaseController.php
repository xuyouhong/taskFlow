<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class BaseController extends Controller
{
    protected function success($data = null, string $message = 'success', int $code = 200): JsonResponse
    {
        return response()->json([
            'data' => $data,
            'message' => $message,
            'status_code' => $code,
        ], $code);
    }

    protected function error(string $message, int $code = 9999, $data = null): JsonResponse
    {
        $httpCode = $code >= 400 ? $code : 400;
        return response()->json([
            'data' => $data,
            'message' => $message,
            'status_code' => $httpCode,
        ], $httpCode);
    }

    protected function paginate($data, string $message = 'success'): JsonResponse
    {
        return $this->success($data, $message);
    }
}
