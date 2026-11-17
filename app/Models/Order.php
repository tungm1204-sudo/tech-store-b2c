<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model Order - Đơn hàng & Vận hành trạng thái
 * Phân hệ nghiệp vụ: Phúc & Trung Anh
 * Thuộc CSDL 16 bảng chuẩn hóa 3NF (Nền tảng Ngày 1-2)
 */
class Order extends Model
{
    public const STATUS_LABELS = [
        'pending'   => 'Chờ xác nhận',
        'confirmed' => 'Đang chuẩn bị hàng',
        'packing'   => 'Đang đóng gói',
        'shipping'  => 'Đang giao hàng',
        'delivered' => 'Hoàn thành',
        'cancelled' => 'Đã hủy',
    ];

    public const STATUS_BADGES = [
        'pending'   => 'warning',
        'confirmed' => 'info',
        'packing'   => 'primary',
        'shipping'  => 'secondary',
        'delivered' => 'success',
        'cancelled' => 'danger',
    ];

    protected $fillable = [
        'order_code',
        'user_id',
        'coupon_id',
        'customer_name',
        'customer_phone',
        'customer_email',
        'shipping_address',
        'note',
        'subtotal',
        'discount_amount',
        'shipping_fee',
        'total_amount',
        'payment_method',
        'payment_status',
        'status',
        'delivered_at',
        'cancelled_reason',
        'updated_by',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'shipping_fee' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'delivered_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function warrantyRequests()
    {
        return $this->hasMany(WarrantyRequest::class);
    }

    public function updatedByUser()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function getStatusBadgeAttribute(): string
    {
        return self::STATUS_BADGES[$this->status] ?? 'dark';
    }

    public function canCustomerCancel(): bool
    {
        return in_array($this->status, ['pending', 'confirmed'], true);
    }
}