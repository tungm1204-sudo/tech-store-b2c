@extends('layouts.ops')

@php
    /** @var \App\Models\Order $order */
    /** @var array $review */
@endphp

@section('title', 'Đơn ' . $order->order_code)
@section('page_title', 'Xử lý đơn ' . $order->order_code)

@section('ops_content')
@php
    $labels = \App\Models\Order::STATUS_LABELS;
    $currentIdx = array_search($order->status, $flow, true);
@endphp

<a href="{{ route('admin.orders.index') }}" class="text-decoration-none small"><i class="bi bi-arrow-left"></i> Danh sách đơn</a>

<div class="card kpi-card my-3"><div class="card-body">
    @if($order->status === 'cancelled')
        <div class="text-danger fw-bold"><i class="bi bi-x-octagon me-1"></i>Đã hủy — {{ $order->cancelled_reason }}</div>
    @else
        <div class="d-flex justify-content-between text-center">
            @foreach($flow as $i => $step)
                <div class="flex-fill">
                    <div class="rounded-circle mx-auto d-flex align-items-center justify-content-center {{ $i <= $currentIdx ? 'bg-success text-white' : 'bg-light text-muted border' }}" style="width:36px;height:36px">
                        @if($i < $currentIdx)<i class="bi bi-check-lg"></i>@else{{ $i + 1 }}@endif
                    </div>
                    <div class="small mt-1 {{ $i === $currentIdx ? 'fw-bold' : 'text-muted' }}">{{ $labels[$step] }}</div>
                </div>
            @endforeach
        </div>
    @endif
</div></div>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card kpi-card mb-3"><div class="card-header bg-white fw-bold">Sản phẩm trong đơn</div>
            <div class="table-responsive"><table class="table align-middle mb-0">
                <thead class="table-light"><tr><th>Sản phẩm</th><th class="text-center">Kho hiện tại</th><th class="text-end">Đơn giá</th><th class="text-center">SL</th><th class="text-end">Thành tiền</th></tr></thead>
                <tbody>
                @foreach($order->items as $item)
                    <tr>
                        <td>{{ $item->product_name }}@if($item->variant_info)<div class="small text-muted">{{ $item->variant_info }}</div>@endif</td>
                        <td class="text-center">{{ $item->product->stock_quantity ?? '—' }}</td>
                        <td class="text-end">{{ number_format($item->unit_price, 0, ',', '.') }}₫</td>
                        <td class="text-center">{{ $item->quantity }}</td>
                        <td class="text-end">{{ number_format($item->line_total, 0, ',', '.') }}₫</td>
                    </tr>
                @endforeach
                </tbody>
                <tfoot class="table-light">
                    <tr><td colspan="4" class="text-end">Tạm tính</td><td class="text-end">{{ number_format((float) $order->subtotal, 0, ',', '.') }}₫</td></tr>
                    <tr><td colspan="4" class="text-end">Giảm giá{{ $order->coupon ? ' (' . $order->coupon->code . ')' : '' }}</td><td class="text-end">-{{ number_format((float) $order->discount_amount, 0, ',', '.') }}₫</td></tr>
                    <tr><td colspan="4" class="text-end">Phí vận chuyển</td><td class="text-end">{{ number_format((float) $order->shipping_fee, 0, ',', '.') }}₫</td></tr>
                    <tr><td colspan="4" class="text-end fw-bold">Tổng cộng</td><td class="text-end fw-bold text-danger">{{ number_format((float) $order->total_amount, 0, ',', '.') }}₫</td></tr>
                </tfoot>
            </table></div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card kpi-card mb-3"><div class="card-body small">
            <div class="fw-bold mb-1">Khách hàng</div>
            {{ $order->customer_name }} • {{ $order->customer_phone }}<br>
            @if($order->customer_email){{ $order->customer_email }}<br>@endif
            {{ $order->shipping_address }}
            @if($order->note)<div class="text-muted mt-1">Ghi chú: {{ $order->note }}</div>@endif
            <hr>
            <div>Thanh toán: <strong>{{ $order->payment_method === 'cod' ? 'COD' : 'Chuyển khoản QR' }}</strong>
                — <span class="badge bg-{{ ['unpaid' => 'warning', 'paid' => 'success', 'refunded' => 'secondary'][$order->payment_status] ?? 'secondary' }}">{{ ['unpaid' => 'Chưa thanh toán', 'paid' => 'Đã thanh toán', 'refunded' => 'Đã hoàn tiền'][$order->payment_status] ?? $order->payment_status }}</span></div>
            @if($order->updatedByUser)<div class="text-muted mt-1">Cập nhật bởi {{ $order->updatedByUser->name }} lúc {{ $order->updated_at->format('d/m/Y H:i') }}</div>@endif
        </div></div>

        @if($order->status === 'pending')
            <div class="card kpi-card mb-3"><div class="card-header bg-white fw-bold"><i class="bi bi-clipboard-check me-1"></i>Kiểm tra đơn mới</div>
                <ul class="list-group list-group-flush small">
                    @foreach($review['checks'] as $c)
                        @php $icon = ['ok' => 'bi-check-circle-fill text-success', 'warn' => 'bi-exclamation-triangle-fill text-warning', 'fail' => 'bi-x-circle-fill text-danger'][$c['status']]; @endphp
                        <li class="list-group-item d-flex gap-2">
                            <i class="bi {{ $icon }} mt-1"></i>
                            <div><div class="fw-semibold">{{ $c['label'] }}</div><div class="text-muted">{{ $c['message'] }}</div></div>
                        </li>
                    @endforeach
                </ul>
                <div class="card-footer bg-white small {{ $review['can_accept'] ? 'text-success' : 'text-danger' }}">
                    @if($review['can_accept'])<i class="bi bi-check2-all me-1"></i>Đơn hợp lệ, có thể duyệt.
                    @else<i class="bi bi-slash-circle me-1"></i>Chưa thể duyệt - hãy xử lý lỗi ở trên hoặc từ chối đơn.@endif
                </div>
            </div>
        @endif

        @if($order->payment_status === 'unpaid' && $order->status !== 'cancelled')
            <form method="POST" action="{{ route('admin.orders.mark_paid', $order) }}" class="mb-3">
                @csrf
                <button class="btn btn-outline-success w-100"><i class="bi bi-cash-coin me-1"></i>Xác nhận đã nhận tiền</button>
            </form>
        @endif

        @if(count($next))
            <div class="card kpi-card"><div class="card-header bg-white fw-bold">Thao tác</div><div class="card-body d-grid gap-2">
                @foreach($next as $to)
                    @if($to === 'cancelled')
                        <form method="POST" action="{{ route('admin.orders.transition', $order) }}" onsubmit="return confirm('Từ chối / hủy đơn này? Tồn kho sẽ được hoàn lại tự động.')">
                            @csrf
                            <input type="hidden" name="to" value="cancelled">
                            <input type="text" name="reason" list="decline-reasons" class="form-control form-control-sm mb-2" placeholder="Lý do từ chối / hủy (bắt buộc)" minlength="5" maxlength="255" required>
                            <datalist id="decline-reasons">
                                <option value="Sản phẩm tạm hết hàng">
                                <option value="Không liên hệ được khách hàng">
                                <option value="Thông tin giao hàng không hợp lệ">
                                <option value="Chưa nhận được tiền chuyển khoản">
                                <option value="Khách yêu cầu hủy đơn">
                            </datalist>
                            <button class="btn btn-outline-danger w-100">{{ $actions[$to] }}</button>
                        </form>
                    @else
                        <form method="POST" action="{{ route('admin.orders.transition', $order) }}">
                            @csrf
                            <input type="hidden" name="to" value="{{ $to }}">
                            <button class="btn btn-primary w-100" @disabled($to === 'confirmed' && ! $review['can_accept'])>{{ $actions[$to] }}</button>
                        </form>
                    @endif
                @endforeach
            </div></div>
        @endif
    </div>
</div>
@endsection
