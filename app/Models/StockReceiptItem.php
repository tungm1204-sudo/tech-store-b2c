<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
