<?php

namespace App\Jobs;

use App\Models\StaticsModel;
use App\Models\CommentModel;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * 评论统计数据处理任务
 *
 * @Description 异步处理评论统计数据入库的队列任务，支持失败重试
 *              自动从 comment 库获取去重后的评论数据，
 *              使用 upsert 实现存在则更新、不存在则插入
 * @Author      Xu YouHong
 * @Date        2026/06/26
 **/
class ProcessCommentStatistics implements ShouldQueue
{
    use Queueable;

    /**
     * 任务最大尝试次数
     *
     * 任务失败后会自动重试，最多重试 3 次
     * 超过最大次数后任务会被标记为失败，进入 failed_jobs 表
     */
    public int $tries = 3;

    /**
     * 任务超时时间（秒）
     *
     * 任务执行超过该时间后会被强制终止，防止死循环或长时间阻塞
     */
    public int $timeout = 120;

    /**
     * 待处理的统计数据
     *
     * 格式：[
     *     ['news_id' => 123, 'num' => 45],
     *     ...
     * ]
     *
     * 当传入空数组时，Job 内部会自动从 CommentModel 获取数据
     *
     * @var array
     */
    protected array $params;

    /**
     * 创建新的任务实例
     *
     * 将待处理的统计数据传入任务，在队列中执行时使用。
     * 如果传入空数组，Job 会在执行时自动从评论库拉取数据。
     *
     * @param array $params 统计数据数组，每个元素包含 news_id 和 num 字段；
     *                      传空数组则由 Job 内部自动获取数据
     */
    public function __construct(array $params)
    {
        $this->params = $params;
    }

    /**
     * 执行任务
     *
     * 执行流程：
     * 1. 如果 params 为空，自动调用 CommentModel::getNewsComment() 获取评论统计数据
     * 2. 调用 StaticsModel::insertOrUpdateCommentStatics 方法批量 upsert 到 comment_statics 表
     * 3. upsert 规则：
     *    - news_id 不存在：插入新记录，设置 created_at 和 updated_at
     *    - news_id 已存在：更新 num 和 updated_at
     *
     * 执行成功记录 info 日志，失败记录 error 日志并抛出异常触发重试
     *
     * @return void
     * @throws \Exception 当数据保存失败时抛出，触发队列重试机制
     */
    public function handle(): void
    {
        echo "========== 评论统计任务开始 ==========\n";
        echo "执行时间: " . now()->toDateTimeString() . "\n";
        echo "任务类型: 评论数据去重统计\n\n";

        $data = $this->params;

        // 如果 params 为空，自动从评论库获取数据
        if (empty($data)) {
            echo "[步骤1] 自动获取评论统计数据...\n";
            Log::info('ProcessCommentStatistics params 为空，自动获取评论统计数据');
            // getNewsComment 返回 Collection，字段为 nc_newsId / total_comments
            // 需要转换为 news_id / num 格式
            $collection = CommentModel::getNewsComment();
            $data = $collection->map(function ($item) {
                $item = (array) $item;
                return [
                    'news_id' => $item['nc_newsId'] ?? null,
                    'num'     => $item['total_comments'] ?? 0,
                ];
            })->filter(function ($item) {
                // 过滤掉 news_id 为空的记录
                return !empty($item['news_id']);
            })->values()->toArray();
            echo "[步骤1] 数据获取完成，共 " . count($data) . " 条记录\n\n";
            Log::info('ProcessCommentStatistics 自动获取数据完成', ['count' => count($data)]);
        } else {
            echo "[步骤1] 使用传入的数据，共 " . count($data) . " 条记录\n\n";
        }

        if (empty($data)) {
            echo "[结果] 无数据需要处理，任务结束\n";
            echo "========== 评论统计任务结束 ==========\n";
            Log::info('ProcessCommentStatistics Job 无数据需要处理');
            return;
        }

        echo "[步骤2] 开始写入 comment_statics 表...\n";
        Log::info('ProcessCommentStatistics Job 开始执行', ['count' => count($data)]);

        // 调用模型方法执行批量 upsert 操作
        $success = StaticsModel::insertOrUpdateCommentStatics($data);

        if ($success) {
            echo "[步骤2] 写入成功！\n\n";
            echo "========== 执行结果 ==========\n";
            echo "状态: 成功\n";
            echo "处理记录数: " . count($data) . "\n";
            echo "完成时间: " . now()->toDateTimeString() . "\n";
            echo "==============================\n";
            Log::info('ProcessCommentStatistics Job 执行成功', ['count' => count($data)]);
        } else {
            echo "[步骤2] 写入失败！\n\n";
            echo "========== 执行结果 ==========\n";
            echo "状态: 失败\n";
            echo "处理记录数: " . count($data) . "\n";
            echo "失败时间: " . now()->toDateTimeString() . "\n";
            echo "==============================\n";
            Log::error('ProcessCommentStatistics Job 执行失败', ['data_count' => count($data)]);
            // 抛出异常，让队列自动重试
            throw new \Exception('评论统计数据保存失败');
        }
    }

    /**
     * 任务最终失败处理
     *
     * 当任务重试次数达到上限后，会调用此方法进行最终的失败处理。
     * 记录详细的失败日志，便于排查问题。
     *
     * @param \Throwable $exception 导致任务失败的异常对象
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('ProcessCommentStatistics Job 最终失败', [
            'error'        => $exception->getMessage(),
            'params_count' => count($this->params),
            'trace'        => $exception->getTraceAsString(),
        ]);
    }
}
