<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model ProductVariant - Biến thể cấu hình (RAM/SSD/Màu sắc)
 * Phân hệ nghiệp vụ: Tuyến
 * Thuộc CSDL 16 bảng chuẩn hóa 3NF (Nền tảng Ngày 1-2)
 */
class ProductVariant extends Model
{
    protected $fillable = [
        'product_id',
        'variant_name',
        'sku_suffix',
        'price_adjustment',
        'stock_quantity',
    ];

    protected $casts = [
        'price_adjustment' => 'decimal:2',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}