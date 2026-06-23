<?php

namespace Admin\Permission\Http\Controllers;

use Illuminate\Routing\Controller as BaseController;
use Illuminate\Http\JsonResponse;

class AdminController extends BaseController
{
    protected function success($data = [], $message = 'Success', $code = 200): JsonResponse
    {
        return response()->json([
            'data'        => $data,
            'message'     => $message,
            'status_code' => $code,
        ], $code);
    }

    protected function error($message = 'Error', $code = 400, $data = []): JsonResponse
    {
        return response()->json([
            'data'        => $data,
            'message'     => $message,
            'status_code' => $code,
        ], $code);
    }

    protected function paginate($data, $message = 'Success'): JsonResponse
    {
        return $this->success([
            'list'         => $data->items(),
            'total'        => $data->total(),
            'current_page' => $data->currentPage(),
            'per_page'     => $data->perPage(),
            'last_page'    => $data->lastPage(),
        ], $message);
    }
}