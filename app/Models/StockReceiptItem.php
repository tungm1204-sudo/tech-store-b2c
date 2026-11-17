<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model StockReceiptItem - Chi tiết sản phẩm nhập kho
 * Phân hệ nghiệp vụ: Trung Anh & Tự
 * Thuộc CSDL 16 bảng chuẩn hóa 3NF (Nền tảng Ngày 1-2)
 */
class StockReceiptItem extends Model
{
    protected $fillable = ['stock_receipt_id', 'product_id', 'quantity', 'unit_cost'];

    protected $casts = ['unit_cost' => 'decimal:2'];

    public function stockReceipt()
    {
        return $this->belongsTo(StockReceipt::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}