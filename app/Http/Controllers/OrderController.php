<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\OrderStateService;
use DomainException;
use Illuminate\Http\Request;

/**
 * Khách hàng: lịch sử đơn hàng, theo dõi tiến trình, tự hủy, xác nhận đã nhận hàng.
 * (Phụ trách: Trung Anh)
 */
class OrderController extends Controller
{
    public function __construct(private OrderStateService $states)
    {
    }

    public function index(Request $request)
    {
        $orders = Order::query()
            ->where('user_id', $request->user()->id)
            ->withCount('items')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->latest()
            ->paginate(10)
            ->appends($request->query());

        return view('orders.index', ['orders' => $orders, 'statuses' => Order::STATUS_LABELS]);
    }

    public function show(Request $request, Order $order)
    {
        $this->ensureOwner($request, $order);

        $order->load(['items.product', 'items.warrantyRequests', 'coupon']);

        return view('orders.show', [
            'order' => $order,
            'flow'  => OrderStateService::FLOW,
        ]);
    }

    public function cancel(Request $request, Order $order)
    {
        $this->ensureOwner($request, $order);
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:255']]);

        try {
            $this->states->cancelByCustomer($order, $request->user(), $data['reason'] ?? null);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Đã hủy đơn hàng {$order->order_code}.");
    }

    public function received(Request $request, Order $order)
    {
        $this->ensureOwner($request, $order);

        try {
            $this->states->confirmReceivedByCustomer($order, $request->user());
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Cảm ơn bạn! Đơn hàng đã được xác nhận hoàn thành.');
    }

    private function ensureOwner(Request $request, Order $order): void
    {
        abort_unless((int) $order->user_id === (int) $request->user()->id, 404);
    }
}
