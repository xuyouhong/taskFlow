<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * 评论数据模型
 *
 * @Description 评论数据库模型，处理评论相关的数据库查询操作
 * @Author      Xu YouHong
 * @Date        2026/06/25 11:13
 **/
class CommentModel extends Model
{
    /**
     * 数据库连接名称
     *
     * @var string
     */
    protected $connection = 'mysql_comment';

    /**
     * 获取新闻评论统计（按结算周期：每月5号至次月4号）
     *
     * 根据当前日期自动计算所属结算周期的时间范围：
     * - 若当前日期 ≤ 4号：统计上月1号 ~ 本月4号
     * - 若当前日期 > 4号：统计本月1号 ~ 下月4号
     *
     * 数据来源为按年分表 nc_news_comment_{year}，仅统计状态正常且已发布的评论。
     * 统计结果按新闻ID分组，返回每篇文章的评论总数和去重用户数。
     *
     * @return Collection 每篇文章的评论统计集合，包含字段：
     *                    - nc_newsId:      文章ID
     *                    - total_comments: 评论总数
     *                    - unique_users:   去重评论用户数
     *
     * @Author      Xu YouHong
     * @Date        2026/06/25 17:02
     */
    public static function getNewsComment(): Collection
    {
        $now = Carbon::now();
        $day = $now->day;

        // 以每月5号为分界，确定结算周期的起止时间戳
        // 例：6月5日 ~ 7月4日 为一个结算周期
        if ($day <= 4) {
            // 当前在1-4号，结算周期为：上月1号 到 本月4号
            $startTime = $now->copy()->startOfMonth()->subMonth()->startOfDay()->timestamp;
            $endTime   = $now->copy()->day(4)->startOfDay()->timestamp;
        } else {
            // 当前在5号及以后，结算周期为：本月1号 到 下月4号
            $startTime = $now->copy()->startOfMonth()->startOfDay()->timestamp;
            $endTime   = $now->copy()->startOfMonth()->addMonth()->day(4)->startOfDay()->timestamp;
        }

        // 按年份动态拼接分表名
        // 评论表按年分表，例如 nc_news_comment_2026
        $table = 'nc_news_comment_' . $now->year;

        return DB::connection('mysql_comment')
            ->table($table)
            ->selectRaw('nc_newsId, COUNT(*) AS total_comments, COUNT(DISTINCT nc_memberId) AS unique_users')
            ->whereBetween('nc_addtime', [$startTime, $endTime])   // 按添加时间过滤
            ->where('nc_status', 1)                                  // 评论状态正常
            ->where('nc_status_time', '>', 0)                        // 已发布（有发布时间）
            ->groupBy('nc_newsId')                                   // 按新闻ID分组
            ->get();
    }
}
