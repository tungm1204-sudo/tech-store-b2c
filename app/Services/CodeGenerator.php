<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;

/**
 * Sinh mã chứng từ theo ngày: PREFIX-YYYYMMDD-NNN (vd: RMA-20261124-004, STK-20261124-002).
 * Phải được gọi BÊN TRONG DB::transaction để lockForUpdate có tác dụng.
 * (Phụ trách: Trung Anh)
 */
class CodeGenerator
{
    /**
     * @param class-string<Model> $modelClass
     */
    public static function next(string $prefix, string $modelClass, string $column): string
    {
        $stem = $prefix . '-' . now()->format('Ymd') . '-';

        $last = $modelClass::query()
            ->where($column, 'like', $stem . '%')
            ->orderByDesc($column)
            ->lockForUpdate()
            ->value($column);

        $seq = $last ? ((int) substr($last, strlen($stem))) + 1 : 1;

        return $stem . str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
    }
}
