<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model ProductImage - Thư viện ảnh sản phẩm
 * Phân hệ nghiệp vụ: Tuyến
 * Thuộc CSDL 16 bảng chuẩn hóa 3NF (Nền tảng Ngày 1-2)
 */
class ProductImage extends Model
{
    protected $fillable = ['product_id', 'image_path', 'is_primary'];

    protected $casts = ['is_primary' => 'boolean'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}