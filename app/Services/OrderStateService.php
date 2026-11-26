<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

/**
 * Máy trạng thái đơn hàng (Phụ trách: Trung Anh)
 *
 *   pending -> confirmed -> packing -> shipping -> delivered
 *      \___________\____________\
 *                   -> cancelled (chỉ được hủy trước khi giao cho đơn vị vận chuyển)
 *
 * Mọi chuyển trạng thái đều chạy trong transaction + lockForUpdate để 2 nhân viên
 * bấm cùng lúc không làm hỏng trạng thái hoặc hoàn kho 2 lần.
 */
class OrderStateService
{
    public const TRANSITIONS = [
        'pending'   => ['confirmed', 'cancelled'],
        'confirmed' => ['packing', 'cancelled'],
        'packing'   => ['shipping', 'cancelled'],
        'shipping'  => ['delivered'],
        'delivered' => [],
        'cancelled' => [],
    ];

    /** Các bước tuyến tính hiển thị trên thanh tiến trình */
    public const FLOW = ['pending', 'confirmed', 'packing', 'shipping', 'delivered'];

    /** Tên nút thao tác cho từng trạng thái đích (giao diện nhân viên) */
    public const ACTION_LABELS = [
        'confirmed' => 'Duyệt đơn (đã kiểm kho)',
        'packing'   => 'Bắt đầu đóng gói',
        'shipping'  => 'Bàn giao vận chuyển',
        'delivered' => 'Xác nhận giao thành công',
        'cancelled' => 'Hủy đơn',
    ];

    public function allowedNext(Order|string $orderOrStatus): array
    {
        $status = $orderOrStatus instanceof Order ? $orderOrStatus->status : $orderOrStatus;

        return self::TRANSITIONS[$status] ?? [];
    }

    public function canTransition(string $from, string $to): bool
    {
        return in_array($to, self::TRANSITIONS[$from] ?? [], true);
    }

    /**
     * Chuyển trạng thái đơn hàng. Ném DomainException nếu bước đi không hợp lệ.
     */
    public function transition(Order $order, string $to, ?User $actor = null, ?string $reason = null): Order
    {
        return DB::transaction(function () use ($order, $to, $actor, $reason) {
            /** @var Order $locked */
            $locked = Order::query()->whereKey($order->getKey())->lockForUpdate()->firstOrFail();

            if (! $this->canTransition($locked->status, $to)) {
                $from = Order::STATUS_LABELS[$locked->status] ?? $locked->status;
                $target = Order::STATUS_LABELS[$to] ?? $to;
                throw new DomainException("Không thể chuyển đơn {$locked->order_code} từ \"{$from}\" sang \"{$target}\".");
            }

            $attrs = [
                'status'     => $to,
                'updated_by' => $actor?->id,
            ];

            if ($to === 'delivered') {
                $attrs['delivered_at'] = now();
                // COD: nhận tiền khi giao thành công
                if ($locked->payment_status === 'unpaid') {
                    $attrs['payment_status'] = 'paid';
                }
            }

            if ($to === 'cancelled') {
                $attrs['cancelled_reason'] = $reason !== null && trim($reason) !== '' ? trim($reason) : 'Đơn hàng bị hủy';
                if ($locked->payment_status === 'paid') {
                    $attrs['payment_status'] = 'refunded';
                }
            }

            $locked->update($attrs);

            if ($to === 'cancelled' && config('techstore.restock_on_cancel', true)) {
                $this->restock($locked);
            }

            return $locked->refresh();
        });
    }

    /** Khách tự hủy đơn (chỉ khi pending / confirmed - xem Order::canCustomerCancel) */
    public function cancelByCustomer(Order $order, User $customer, ?string $reason = null): Order
    {
        if ((int) $order->user_id !== (int) $customer->id) {
            throw new DomainException('Bạn không có quyền thao tác trên đơn hàng này.');
        }
        if (! $order->canCustomerCancel()) {
            throw new DomainException('Đơn hàng đã được đóng gói / giao đi nên không thể tự hủy. Vui lòng liên hệ cửa hàng.');
        }

        return $this->transition($order, 'cancelled', $customer, $reason ?: 'Khách hàng tự hủy đơn');
    }

    /** Khách bấm "Đã nhận hàng" khi đơn đang giao */
    public function confirmReceivedByCustomer(Order $order, User $customer): Order
    {
        if ((int) $order->user_id !== (int) $customer->id) {
            throw new DomainException('Bạn không có quyền thao tác trên đơn hàng này.');
        }

        return $this->transition($order, 'delivered', $customer);
    }

    /** Nhân viên xác nhận đã nhận tiền chuyển khoản (QR) / thu tiền */
    public function markPaid(Order $order, ?User $actor = null): Order
    {
        return DB::transaction(function () use ($order, $actor) {
            $locked = Order::query()->whereKey($order->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->status === 'cancelled') {
                throw new DomainException('Đơn hàng đã hủy, không thể ghi nhận thanh toán.');
            }
            if ($locked->payment_status !== 'unpaid') {
                throw new DomainException('Đơn hàng này không ở trạng thái "Chưa thanh toán".');
            }

            $locked->update(['payment_status' => 'paid', 'updated_by' => $actor?->id]);

            return $locked->refresh();
        });
    }

    /** Hoàn lại tồn kho (sản phẩm + biến thể) cho các dòng hàng của đơn bị hủy */
    protected function restock(Order $order): void
    {
        foreach ($order->items()->get() as $item) {
            Product::query()->whereKey($item->product_id)->increment('stock_quantity', $item->quantity);

            if ($item->variant_id) {
                ProductVariant::query()->whereKey($item->variant_id)->increment('stock_quantity', $item->quantity);
            }
        }
    }
}
