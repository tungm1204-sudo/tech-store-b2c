<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model CartItem - Chi tiết món hàng trong giỏ
 * Phân hệ nghiệp vụ: Phúc
 * Thuộc CSDL 16 bảng chuẩn hóa 3NF (Nền tảng Ngày 1-2)
 */
class CartItem extends Model
{
    protected $fillable = ['cart_id', 'product_id', 'variant_id', 'quantity'];

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function getUnitPriceAttribute(): float
    {
        $base = $this->product ? $this->product->effective_price : 0.0;
        $adj = $this->variant ? (float) $this->variant->price_adjustment : 0.0;
        return max(0.0, $base + $adj);
    }

    public function getLineTotalAttribute(): float
    {
        return $this->unit_price * $this->quantity;
    }
}