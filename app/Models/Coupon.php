<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model Coupon - Mã giảm giá & Khuyến mãi
 * Phân hệ nghiệp vụ: Phúc
 * Thuộc CSDL 16 bảng chuẩn hóa 3NF (Nền tảng Ngày 1-2)
 */
class Coupon extends Model
{
    protected $fillable = [
        'code',
        'discount_type',
        'discount_value',
        'min_order_amount',
        'max_uses',
        'used_count',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'min_order_amount' => 'decimal:2',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function isValidForSubtotal(float $subtotal): bool
    {
        if (!$this->is_active) {
            return false;
        }
        if ($this->used_count >= $this->max_uses) {
            return false;
        }
        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }
        if ($subtotal < (float) $this->min_order_amount) {
            return false;
        }
        return true;
    }

    public function calculateDiscount(float $subtotal): float
    {
        if (!$this->isValidForSubtotal($subtotal)) {
            return 0.0;
        }
        if ($this->discount_type === 'percent') {
            return min($subtotal, round($subtotal * ((float) $this->discount_value / 100), 0));
        }
        return min($subtotal, (float) $this->discount_value);
    }
}
