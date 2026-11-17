<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model UserAddress - Sổ địa chỉ giao hàng cá nhân
 * Phân hệ nghiệp vụ: Điền
 * Thuộc CSDL 16 bảng chuẩn hóa 3NF (Nền tảng Ngày 1-2)
 */
class UserAddress extends Model
{
    protected $fillable = [
        'user_id',
        'recipient_name',
        'recipient_phone',
        'province',
        'district',
        'ward',
        'street_address',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->street_address,
            $this->ward,
            $this->district,
            $this->province,
        ]);
        return implode(', ', $parts);
    }
}