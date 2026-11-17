<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model WarrantyRequest (Đổi trả & Bảo hành thiết bị - Phụ trách: Trung Anh)
 */
class WarrantyRequest extends Model
{
    public const STATUS_LABELS = [
        'submitted'  => 'Yêu cầu mới',
        'inspecting' => 'Đang kiểm tra kỹ thuật',
        'approved'   => 'Đã duyệt đổi mới / sửa chữa',
        'rejected'   => 'Từ chối bảo hành',
        'completed'  => 'Đã hoàn tất',
    ];

    public const STATUS_BADGES = [
        'submitted'  => 'warning',
        'inspecting' => 'info',
        'approved'   => 'primary',
        'rejected'   => 'danger',
        'completed'  => 'success',
    ];

    protected $fillable = [
        'rma_code',
        'order_id',
        'order_item_id',
        'user_id',
        'serial_number',
        'request_type',
        'issue_description',
        'evidence_url',
        'status',
        'resolution_note',
        'handled_by',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function handler()
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUS_LABELS[$this->status] ?? $this->status;
    }

    public function getStatusBadgeAttribute(): string
    {
        return self::STATUS_BADGES[$this->status] ?? 'secondary';
    }
}
