<?php

namespace App\Services;

use App\Models\Order;

class OrderReviewService
{
    public const OK = 'ok';
    public const WARN = 'warn';
    public const FAIL = 'fail';

    /**
     * @return array{checks: array<int,array{key:string,label:string,status:string,message:string}>, blockers: array<int,string>, can_accept: bool}
     */
    public function review(Order $order): array
    {
        $order->loadMissing(['items.product', 'user']);

        $checks = [
            $this->checkItems($order),
            $this->checkTotals($order),
            $this->checkPayment($order),
            $this->checkContact($order),
            $this->checkProducts($order),
            $this->checkStockHeld($order),
        ];

        $blockers = [];
        foreach ($checks as $c) {
            if ($c['status'] === self::FAIL) {
                $blockers[] = $c['message'];
            }
        }

        return [
            'checks'     => $checks,
            'blockers'   => $blockers,
            'can_accept' => $blockers === [],
        ];
    }

    private function make(string $key, string $label, string $status, string $message): array
    {
        return compact('key', 'label', 'status', 'message');
    }

    private function checkItems(Order $order): array
    {
        if ($order->items->isEmpty()) {
            return $this->make('items', 'Dòng hàng', self::FAIL, 'Đơn hàng không có sản phẩm nào.');
        }

        return $this->make('items', 'Dòng hàng', self::OK, $order->items->count() . ' dòng hàng, tổng ' . $order->items->sum('quantity') . ' sản phẩm.');
    }

    private function checkTotals(Order $order): array
    {
        $sum = 0.0;
        foreach ($order->items as $item) {
            $expected = round((float) $item->unit_price * (int) $item->quantity);
            if (round((float) $item->line_total) !== (float) $expected) {
                return $this->make('totals', 'Số tiền', self::FAIL, "Thành tiền dòng \"{$item->product_name}\" không khớp đơn giá × số lượng.");
            }
            $sum += (float) $item->line_total;
        }

        $expectedTotal = max(0, $sum - (float) $order->discount_amount + (float) $order->shipping_fee);
        if (round((float) $order->total_amount) !== round($expectedTotal)) {
            return $this->make('totals', 'Số tiền', self::FAIL, 'Tổng tiền đơn hàng không khớp với các dòng hàng, giảm giá và phí vận chuyển.');
        }

        return $this->make('totals', 'Số tiền', self::OK, 'Tổng tiền khớp: ' . number_format((float) $order->total_amount, 0, ',', '.') . '₫.');
    }

    private function checkPayment(Order $order): array
    {
        if ($order->payment_status === 'refunded') {
            return $this->make('payment', 'Thanh toán', self::FAIL, 'Đơn đã được hoàn tiền nên không thể duyệt.');
        }

        if ($order->payment_method === 'qr_transfer') {
            if ($order->payment_status === 'paid') {
                return $this->make('payment', 'Thanh toán', self::OK, 'Đã nhận tiền chuyển khoản QR.');
            }

            return $this->make('payment', 'Thanh toán', self::FAIL, 'Chưa nhận được tiền chuyển khoản QR. Hãy đối soát ngân hàng và bấm "Xác nhận đã nhận tiền", hoặc từ chối đơn.');
        }

        return $this->make('payment', 'Thanh toán', self::OK, 'COD - thu tiền khi giao hàng.');
    }

    private function checkContact(Order $order): array
    {
        $digits = preg_replace('/\D+/', '', (string) $order->customer_phone);
        if (strlen($digits) < 8) {
            return $this->make('contact', 'Thông tin giao nhận', self::FAIL, 'Số điện thoại người nhận không hợp lệ.');
        }
        if (trim((string) $order->shipping_address) === '') {
            return $this->make('contact', 'Thông tin giao nhận', self::FAIL, 'Thiếu địa chỉ giao hàng.');
        }
        if ($order->user && ! $order->user->is_active) {
            return $this->make('contact', 'Thông tin giao nhận', self::WARN, 'Tài khoản khách hàng đang bị khóa - cân nhắc trước khi duyệt.');
        }

        return $this->make('contact', 'Thông tin giao nhận', self::OK, $order->customer_name . ' - ' . $order->customer_phone . '.');
    }

    private function checkProducts(Order $order): array
    {
        $inactive = [];
        foreach ($order->items as $item) {
            if (! $item->product || ! $item->product->is_active) {
                $inactive[] = $item->product_name;
            }
        }

        if ($inactive) {
            return $this->make('products', 'Sản phẩm', self::WARN, 'Đã ngừng kinh doanh: ' . implode(', ', $inactive) . '.');
        }

        return $this->make('products', 'Sản phẩm', self::OK, 'Tất cả sản phẩm còn kinh doanh.');
    }

    private function checkStockHeld(Order $order): array
    {
        if ($order->stock_deducted_at) {
            return $this->make('stock', 'Giữ hàng trong kho', self::OK, 'Kho đã trừ lúc đặt hàng (' . $order->stock_deducted_at->format('d/m/Y H:i') . ').');
        }

        return $this->make('stock', 'Giữ hàng trong kho', self::WARN, 'Đơn không có dấu trừ kho (đơn mẫu/đơn cũ) - hãy kiểm tra tồn kho thực tế.');
    }
}
