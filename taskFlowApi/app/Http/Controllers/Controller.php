<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;

abstract class Controller
{
    /***
     * @Description    返回成功JSON数据
     * @param int    $code    成功状态码
     * @param string $message 成功消息
     * @param array  $data    成功数据
     * @return JsonResponse 响应JSON数据
     * @Author         Xu YouHong
     * @Date           2025/11/6 16:25
     **/
    public function successJsonOut(string $message = 'SUCCESS', array $data = [], int $code = 200): JsonResponse
    {
        return response()->json(['code' => $code, 'message' => $message, 'data' => $data])
            ->header('Content-Type', 'application/json');
    }

    /***
     * @Description     返回错误JSON数据
     * @param int    $code    错误状态码
     * @param string $message 错误消息
     * @param array  $data    错误数据
     * @return JsonResponse
     * @Author          Xu YouHong
     * @Date            2025/11/6 16:26
     **/
    public function errorJsonOut(string $message = '', array $data = [], int $code = 99999): JsonResponse
    {
        return response()->json(['code' => $code, 'message' => $message, 'data' => $data])
            ->header('Content-Type', 'application/json');
    }
}
