<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model OrderItem - Chi tiết sản phẩm trong đơn hàng
 * Phân hệ nghiệp vụ: Phúc & Trung Anh
 * Thuộc CSDL 16 bảng chuẩn hóa 3NF (Nền tảng Ngày 1-2)
 */
class OrderItem extends Model
{
    protected $fillable = [
        'order_id',
        'product_id',
        'variant_id',
        'product_name',
        'variant_info',
        'unit_price',
        'quantity',
        'line_total',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'line_total' => 'decimal:2',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function warrantyRequests()
    {
        return $this->hasMany(WarrantyRequest::class);
    }
}