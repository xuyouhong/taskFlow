<?php

namespace App\Traits;

use Hashids\Hashids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * 为 Eloquent Model 提供 Hash ID 主键能力。
 *
 * - 主键 hash_id 为 VARCHAR(20) 字符串，由 Hashids 编码生成。
 * - 内部 _seq 为 AUTO_INCREMENT 列，用于编码输入，不对外暴露。
 * - 不同模型使用基于表名的独立 salt，确保相同序列号产生不同 hash。
 */
trait HasHashId
{
    /**
     * 启动 trait：在 creating 事件中生成 hash_id。
     */
    public static function bootHasHashId(): void
    {
        static::creating(function (Model $model) {
            if (empty($model->hash_id)) {
                $table = $model->getTable();
                // 预获取下一个自增ID，确保并发安全
                $nextSeq = DB::table($table)->max('_seq') + 1;
                $model->_seq = $nextSeq;
                $model->hash_id = static::encodeHashId($nextSeq);
            }
        });
    }

    /**
     * 转为数组时，隐藏内部 _seq 列并清除 pivot 数据。
     * hash_id 作为主键自然包含在 toArray() 输出中。
     */
    public function toArray(): array
    {
        $array = parent::toArray();

        // 隐藏内部序列号
        unset($array['_seq']);

        // 清除 BelongsToMany 关联产生的 pivot 数据
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = static::stripPivot($value);
            }
        }

        return $array;
    }

    /**
     * 递归移除数组中的 pivot 键。
     */
    private static function stripPivot(array $array): array
    {
        unset($array['pivot']);

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = static::stripPivot($value);
            }
        }

        return $array;
    }

    /**
     * 获取当前模型用于 Hash ID 编码的 salt。
     */
    public static function getHashIdSalt(): string
    {
        $baseSalt = config('permission.hashids.salt', 'task-scheduler');
        return $baseSalt . ':' . (new static)->getTable();
    }

    /**
     * 获取 Hashids 实例。
     */
    protected static function makeHashids(): Hashids
    {
        $minLength = (int)config('permission.hashids.min_length', 8);
        return new Hashids(static::getHashIdSalt(), $minLength);
    }

    /**
     * 将序列号编码为 Hash ID 字符串。
     */
    public static function encodeHashId(int $id): string
    {
        return static::makeHashids()->encode($id);
    }

    /**
     * 将 Hash ID 字符串解码为序列号。
     * 返回 null 表示解码失败。
     */
    public static function decodeHashId(string $hashId): ?int
    {
        $ids = static::makeHashids()->decode($hashId);
        return count($ids) > 0 ? (int)$ids[0] : null;
    }

    /**
     * 获取内部序列号。
     */
    public function getSeqAttribute(): ?int
    {
        return $this->attributes['_seq'] ?? null;
    }
}

