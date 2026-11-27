<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\OrderReviewService;
use App\Services\OrderStateService;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderManagementController extends Controller
{
    public function __construct(
        private OrderStateService $states,
        private OrderReviewService $reviews
    ) {
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
            'review'  => $this->reviews->review($order),
        ]);
    }

    public function transition(Request $request, Order $order)
    {
        $data = $request->validate([
            'to'     => ['required', Rule::in(array_keys(Order::STATUS_LABELS))],
            'reason' => [Rule::requiredIf($request->input('to') === 'cancelled'), 'nullable', 'string', 'min:5', 'max:255'],
        ], [
            'reason.required' => 'Vui lòng nhập lý do từ chối / hủy đơn để khách hàng biết.',
            'reason.min'      => 'Lý do từ chối / hủy đơn cần ít nhất 5 ký tự.',
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
