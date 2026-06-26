<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Exception;

/**
 * 统计数据模型
 *
 * @Description 统计数据库模型，处理各类统计数据的数据库操作
 * @Author      Xu YouHong
 * @Date        2026/06/25 11:13
 **/
class StaticsModel extends Model
{
    protected $connection = 'mysql_statics';

    /**
     * 批量插入或更新评论统计数据（基于唯一键 news_id）
     *
     * 采用"先查后写"策略避免 upsert 导致自增 ID 不连续的问题
     *
     * @param array $params 待处理的数据，支持二维数组或单条一维数组
     * @return bool
     */
    public static function insertOrUpdateCommentStatics(array $params = []): bool
    {
        if (empty($params)) {
            return true;
        }

        $data = isset($params[0]) && is_array($params[0]) ? $params : [$params];
        $now = now();

        try {
            // 批量查询已存在的 news_id
            $newsIds = array_column($data, 'news_id');
            $existingIds = DB::connection('mysql_statics')->table('comment_statics')
                ->whereIn('news_id', $newsIds)
                ->pluck('news_id')
                ->flip()
                ->toArray();

            // 按是否存在分组
            [$insertData, $updateData] = collect($data)->partition(
                fn($item) => !isset($existingIds[$item['news_id']])
            );

            // 新增：insert（消耗自增 ID）
            if ($insertData->isNotEmpty()) {
                DB::connection('mysql_statics')->table('comment_statics')
                    ->insert($insertData->map(fn($item) => [
                        'news_id' => $item['news_id'],
                        'num' => $item['num'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])->toArray());
            }

            // 更新：批量 CASE WHEN（不消耗自增 ID）
            if ($updateData->isNotEmpty()) {
                $cases = $updateData->map(fn($item) => "WHEN {$item['news_id']} THEN {$item['num']}")->implode(' ');
                DB::connection('mysql_statics')->table('comment_statics')
                    ->whereIn('news_id', $updateData->pluck('news_id')->toArray())
                    ->update([
                        'num' => DB::raw("CASE news_id {$cases} END"),
                        'updated_at' => $now,
                    ]);
            }

            return true;
        } catch (Exception $e) {
            \Log::error('评论统计数据保存失败', [
                'error' => $e->getMessage(),
                'data' => $params,
            ]);
            return false;
        }
    }
}
