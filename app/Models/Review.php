<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model Review - Đánh giá & Bình luận sản phẩm (Verified Purchase)
 * Phân hệ nghiệp vụ: Nguyễn Minh Tự
 * Thuộc CSDL 16 bảng chuẩn hóa 3NF (Nền tảng Ngày 1-2)
 */
class Review extends Model
{
    protected $fillable = [
        'product_id',
        'user_id',
        'order_id',
        'rating',
        'title',
        'pros',
        'cons',
        'comment',
        'is_verified_purchase',
        'is_approved',
        'admin_reply',
        'replied_at',
        'replied_by',
    ];

    protected $casts = [
        'is_verified_purchase' => 'boolean',
        'is_approved' => 'boolean',
        'replied_at' => 'datetime',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function replier()
    {
        return $this->belongsTo(User::class, 'replied_by');
    }
}