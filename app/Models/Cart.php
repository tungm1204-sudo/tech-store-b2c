<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model Cart - Giỏ hàng lưu trữ database
 * Phân hệ nghiệp vụ: Phúc
 * Thuộc CSDL 16 bảng chuẩn hóa 3NF (Nền tảng Ngày 1-2)
 */
class Cart extends Model
{
    protected $fillable = ['user_id', 'session_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(CartItem::class);
    }
}