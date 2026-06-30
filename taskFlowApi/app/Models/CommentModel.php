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
     * 获取新闻评论统计（按结算周期）
     * 结算周期：每月5号为分界，例如 6月5日 ~ 7月4日 为一个周期
     * 统计维度：每个新闻ID的总评论数(total_comments)和独立用户数(unique_users)
     *
     *
     * @return Collection 每篇文章的评论统计集合，包含字段：
     *                     - nc_newsId:      文章ID
     *                     - total_comments: 评论总数
     *                     - unique_users:   去重评论用户数
     *
     * @Author      Xu YouHong
     * @Date        2026/06/25 17:02
     */
    public static function getNewsComment(): Collection
    {
        $now = Carbon::now();
        $day = $now->day;

        // ---- 1. 计算结算周期的起止时间戳 ----
        if ($day <= 4) {
            // 当前在1-4号：周期为「上月1日 00:00:00」至「本月4日 00:00:00」
            $startTime = $now->copy()->startOfMonth()->subMonth()->startOfDay()->timestamp;
            $endTime   = $now->copy()->day(4)->startOfDay()->timestamp;
        } else {
            // 当前在5号及以后：周期为「本月1日 00:00:00」至「下月4日 00:00:00」
            $startTime = $now->copy()->startOfMonth()->startOfDay()->timestamp;
            $endTime   = $now->copy()->startOfMonth()->addMonth()->day(4)->startOfDay()->timestamp;
        }

        // ---- 2. 定义公共查询条件（闭包） ----
        $buildQuery = function ($tableName) use ($startTime, $endTime) {
            return DB::connection('mysql_comment')
                ->table($tableName)
                ->selectRaw('nc_newsId, COUNT(*) AS total_comments, COUNT(DISTINCT nc_memberId) AS unique_users')
                ->whereBetween('nc_addtime', [$startTime, $endTime])
                ->where('nc_status', 1)                     // 审核通过
                ->where('nc_status_time', '>', 0)     // 有审核时间
                ->groupBy('nc_newsId')
                ->get();
        };

        // ---- 3. 执行查询 ----
        if ($now->month == 1) {
            // 1月份需要跨年查询（去年表 + 今年表）
            $lastYearTable = 'nc_news_comment_' . ($now->year - 1);
            $thisYearTable = 'nc_news_comment_' . $now->year;

            $listLastYear = $buildQuery($lastYearTable);
            $listThisYear = $buildQuery($thisYearTable);

            // ---- 4. 合并两个集合，按 nc_newsId 聚合 ----
            return $listLastYear->merge($listThisYear)
                ->groupBy('nc_newsId')
                ->map(function ($items, $newsId) {
                    // 对相同新闻ID的统计值求和
                    $sumTotal = $items->sum('total_comments');
                    $sumUsers = $items->sum('unique_users');
                    return (object)[
                        'nc_newsId'      => $newsId,
                        'total_comments' => $sumTotal,
                        'unique_users'   => $sumUsers,
                    ];
                })
                ->values(); // 重置索引，返回普通集合
        }

        // 非1月份，只查当前年份表
        $tableName = 'nc_news_comment_' . $now->year;
        return $buildQuery($tableName);
    }
}
