<?php

namespace App\View\Components;

use App\Models\Order;
use App\Models\Product;
use App\Models\StockReceipt;
use App\Models\WarrantyRequest;
use Illuminate\View\Component;

/**
 * Thanh điều hướng module Vận hành (Đơn hàng / RMA / Nhập kho) kèm số việc cần xử lý.
 * Dùng trong layouts/ops.blade.php:  <x-ops-nav />   (Phụ trách: Trung Anh)
 */
class OpsNav extends Component
{
    public array $tabs;

    public function __construct()
    {
        $this->tabs = [
            [
                'label'  => 'Đơn hàng',
                'icon'   => 'bi-receipt-cutoff',
                'route'  => 'admin.orders.index',
                'match'  => 'admin.orders.*',
                'count'  => Order::where('status', 'pending')->count(),
                'hint'   => 'đơn chờ xác nhận',
                'tone'   => 'warning',
            ],
            [
                'label'  => 'Đổi trả & Bảo hành',
                'icon'   => 'bi-tools',
                'route'  => 'admin.warranties.index',
                'match'  => 'admin.warranties.*',
                'count'  => WarrantyRequest::whereIn('status', ['submitted', 'inspecting'])->count(),
                'hint'   => 'yêu cầu đang chờ xử lý',
                'tone'   => 'info',
            ],
            [
                'label'  => 'Nhập kho',
                'icon'   => 'bi-box-seam',
                'route'  => 'admin.stock_receipts.index',
                'match'  => 'admin.stock_receipts.*',
                'count'  => StockReceipt::where('status', 'pending')->count(),
                'hint'   => 'phiếu chờ duyệt',
                'tone'   => 'primary',
            ],
        ];
    }

    /** Số sản phẩm sắp hết hàng (hiển thị ở thanh cảnh báo) */
    public function lowStockCount(): int
    {
        return Product::where('is_active', true)->whereColumn('stock_quantity', '<=', 'low_stock_threshold')->count();
    }

    public function render()
    {
        return view('components.ops-nav');
    }
}
