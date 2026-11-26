<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

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

    public const FLOW = ['pending', 'confirmed', 'packing', 'shipping', 'delivered'];

    public const ACTION_LABELS = [
        'confirmed' => 'Duyệt đơn (đã kiểm kho)',
        'packing'   => 'Bắt đầu đóng gói',
        'shipping'  => 'Bàn giao vận chuyển',
        'delivered' => 'Xác nhận giao thành công',
        'cancelled' => 'Từ chối / Hủy đơn',
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

            if ($to === 'confirmed') {
                $review = app(OrderReviewService::class)->review($locked);
                if (! $review['can_accept']) {
                    throw new DomainException('Không thể duyệt đơn ' . $locked->order_code . ': ' . implode(' ', $review['blockers']));
                }
            }

            if ($to === 'cancelled' && $actor && $actor->isStaffOrAdmin() && mb_strlen(trim((string) $reason)) < 5) {
                throw new DomainException('Vui lòng nhập lý do từ chối / hủy đơn (ít nhất 5 ký tự).');
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

            return $locked->refresh();
        });
    }

    public function cancelByCustomer(Order $order, User $customer, ?string $reason = null): Order
    {
        if ($order->user_id !== $customer->id) {
            throw new DomainException('Bạn không có quyền thao tác trên đơn hàng này.');
        }
        if (! $order->canCustomerCancel()) {
            throw new DomainException('Đơn hàng đã được đóng gói / giao đi nên không thể tự hủy. Vui lòng liên hệ cửa hàng.');
        }

        return $this->transition($order, 'cancelled', $customer, $reason ?: 'Khách hàng tự hủy đơn');
    }

    public function confirmReceivedByCustomer(Order $order, User $customer): Order
    {
        if ($order->user_id !== $customer->id) {
            throw new DomainException('Bạn không có quyền thao tác trên đơn hàng này.');
        }

        return $this->transition($order, 'delivered', $customer);
    }

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
}
