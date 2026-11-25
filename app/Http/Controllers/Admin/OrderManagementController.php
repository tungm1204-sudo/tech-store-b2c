<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderStateService;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Nhân viên / Admin: tiếp nhận & xử lý đơn hàng theo các bước trạng thái.
 * (Phụ trách: Trung Anh)
 */
class OrderManagementController extends Controller
{
    public function __construct(private OrderStateService $states)
    {
    }

    public function index(Request $request)
    {
        $orders = Order::query()
            ->withCount('items')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('payment_status'), fn ($q) => $q->where('payment_status', $request->input('payment_status')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $kw = trim($request->input('q'));
                $q->where(function ($w) use ($kw) {
                    $w->where('order_code', 'like', "%{$kw}%")
                      ->orWhere('customer_name', 'like', "%{$kw}%")
                      ->orWhere('customer_phone', 'like', "%{$kw}%");
                });
            })
            ->latest()
            ->paginate(15)
            ->appends($request->query());

        $counts = Order::query()->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('admin.orders.index', [
            'orders'   => $orders,
            'counts'   => $counts,
            'statuses' => Order::STATUS_LABELS,
        ]);
    }

    public function show(Order $order)
    {
        $order->load(['items.product', 'user', 'coupon', 'updatedByUser']);

        return view('admin.orders.show', [
            'order'   => $order,
            'flow'    => OrderStateService::FLOW,
            'next'    => $this->states->allowedNext($order),
            'actions' => OrderStateService::ACTION_LABELS,
        ]);
    }

    public function transition(Request $request, Order $order)
    {
        $data = $request->validate([
            'to'     => ['required', Rule::in(array_keys(Order::STATUS_LABELS))],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->states->transition($order, $data['to'], $request->user(), $data['reason'] ?? null);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Đơn {$order->order_code}: đã chuyển sang \"" . Order::STATUS_LABELS[$data['to']] . '".');
    }

    public function markPaid(Request $request, Order $order)
    {
        try {
            $this->states->markPaid($order, $request->user());
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Đã ghi nhận thanh toán cho đơn {$order->order_code}.");
    }
}
