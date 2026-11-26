<?php

namespace App\Observers;

use App\Models\Order;
use App\Services\OrderInventoryService;

/**
 * Điểm nối giữa Checkout (Phúc) và luồng hủy đơn (Trung Anh).
 * Chỉ hoàn kho khi status thực sự chuyển sang cancelled và đơn đã trừ kho.
 */
class OrderObserver
{
    public function updated(Order $order): void
    {
        if ($order->wasChanged('status') && $order->status === 'cancelled') {
            app(OrderInventoryService::class)->restoreForCancelledOrder((int) $order->id);
        }
    }
}
