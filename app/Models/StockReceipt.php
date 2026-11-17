<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Model StockReceipt (Phiếu nhập kho lô hàng & IMEI/Serial - Phụ trách: Trung Anh & Tự)
 */
class StockReceipt extends Model
{
    protected $fillable = [
        'receipt_code',
        'supplier_name',
        'imei_batch_note',
        'created_by',
        'approved_by',
        'total_cost',
        'status',
        'approved_at',
    ];

    protected $casts = [
        'total_cost' => 'decimal:2',
        'approved_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(StockReceiptItem::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
