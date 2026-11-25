<?php

namespace App\Services;

use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

/**
 * Quản lý tồn kho dùng chung giữa Checkout (Phúc) và hủy đơn (Trung Anh).
 */
class OrderInventoryService
{
    /**
     * Hoàn kho đúng một lần khi đơn đã chuyển sang cancelled.
     * Chỉ hoàn các đơn có stock_deducted_at do Checkout ghi nhận.
     */
    public function restoreForCancelledOrder(int $orderId): bool
    {
        return DB::transaction(function () use ($orderId) {
            $order = Order::whereKey($orderId)->lockForUpdate()->first();

            if (
                !$order || $order->status !== 'cancelled' ||
                !$order->stock_deducted_at || $order->stock_restored_at
            ) {
                return false;
            }

            $items = $order->items()->orderBy('product_id')->orderBy('id')->get();

            foreach ($items as $item) {
                $product = Product::whereKey($item->product_id)->lockForUpdate()->first();
                if (!$product) {
                    continue;
                }

                $product->stock_quantity += (int) $item->quantity;
                // Checkout đã cộng sold_count khi chốt đơn nên phải trừ lại khi hủy.
                $product->sold_count = max(0, (int) $product->sold_count - (int) $item->quantity);
                $product->save();

                if ($item->variant_id) {
                    $variant = ProductVariant::whereKey($item->variant_id)
                        ->where('product_id', $product->id)
                        ->lockForUpdate()
                        ->first();

                    if ($variant) {
                        $variant->stock_quantity += (int) $item->quantity;
                        $variant->save();
                    }
                }
            }

            // Trả lại lượt dùng mã giảm giá đã bị Checkout tính khi đặt đơn.
            if ($order->coupon_id) {
                $coupon = Coupon::whereKey($order->coupon_id)->lockForUpdate()->first();
                if ($coupon && $coupon->used_count > 0) {
                    $coupon->used_count = (int) $coupon->used_count - 1;
                    $coupon->save();
                }
            }

            $order->stock_restored_at = now();
            $order->save();

            return true;
        }, 3);
    }
}
