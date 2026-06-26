<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessCommentStatistics;
use App\Models\CommentModel;
use App\Models\StaticsModel;
use Illuminate\Http\Request;

/**
 * 统计数据控制器
 *
 * @Description 处理各类统计数据的接口，包括评论统计、数据同步等
 * @Author      Xu YouHong
 * @Date        2026/06/25
 **/
class StaticsController extends Controller
{
    /**
     * 判断是否同步执行任务
     *
     * 读取环境变量 TASK_EXECUTION_MODE 的值，判断任务执行模式：
     * - sync: 同步模式，直接执行，便于调试
     * - async: 异步模式，推送到队列，生产环境推荐
     *
     * @return bool true-同步模式, false-异步模式
     */
    private function isSyncMode(): bool
    {
        return strtolower(env('TASK_EXECUTION_MODE', 'async')) === 'sync';
    }

    /**
     * 评论去重统计接口
     *
     * 获取结算周期内的新闻评论统计数据，并存入 comment_statics 表。
     * 使用 upsert 实现存在则更新、不存在则插入的逻辑。
     *
     * 结算周期规则（每月5号至次月4号）：
     * - 当前日期 ≤ 4号：统计上月1号 ~ 本月4号
     * - 当前日期 > 4号：统计本月1号 ~ 下月4号
     *
     * 执行模式（通过 .env 的 TASK_EXECUTION_MODE 配置）：
     * - sync(同步): 直接执行入库操作，等待完成后返回结果，便于调试
     * - async(异步): 推送到队列，由队列worker后台执行，生产环境推荐
     *
     * @return \Illuminate\Http\JsonResponse 返回处理结果
     *                                - 成功: code=200, 包含处理数量和执行模式
     *                                - 失败: code=99999, 包含错误信息
     */
    public function deduplicationCommentStatistics()
    {
        // 1. 获取评论统计数据
        $commentModel = new CommentModel();
        $query        = $commentModel->getNewsComment();
        $query        = json_decode(json_encode($query), true);

        // 无数据时直接返回成功
        if (empty($query)) {
            return $this->successJsonOut('SUCCESS', ['message' => 'No data to process', 'count' => 0]);
        }

        // 2. 格式化数据，转换为 comment_statics 表需要的字段格式
        $params = [];
        foreach ($query as $item) {
            $params[] = [
                'news_id' => $item['nc_newsId'],  // 新闻ID
                'num'     => $item['unique_users'], // 去重后的评论用户数
            ];
        }

        // 3. 根据配置决定同步或异步执行
        if ($this->isSyncMode()) {
            // 同步模式：直接调用方法执行入库，等待结果
            $success = StaticsModel::insertOrUpdateCommentStatics($params);

            if ($success) {
                return $this->successJsonOut('SUCCESS', [
                    'message' => 'Data saved successfully (sync mode)',
                    'count'   => count($params),
                    'mode'    => 'sync',
                ]);
            }

            return $this->errorJsonOut('Failed to save data', ['count' => count($params), 'mode' => 'sync']);
        } else {
            // 异步模式：将任务推送到队列，立即返回成功响应
            // 队列 worker 会在后台执行 ProcessCommentStatistics Job
            ProcessCommentStatistics::dispatch($params);

            return $this->successJsonOut('SUCCESS', [
                'message' => 'Task dispatched to queue (async mode)',
                'count'   => count($params),
                'mode'    => 'async',
            ]);
        }
    }
}
