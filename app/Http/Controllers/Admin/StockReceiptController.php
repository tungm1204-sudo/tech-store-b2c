<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockReceipt;
use App\Services\StockReceiptService;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;

/**
 * Nhập kho & cảnh báo sắp hết hàng. (Phụ trách: Trung Anh)
 * Nhân viên lập phiếu; chỉ Admin duyệt -> tồn kho được cộng tự động.
 */
class StockReceiptController extends Controller
{
    public function __construct(private StockReceiptService $stock)
    {
    }

    public function index(Request $request)
    {
        $receipts = StockReceipt::query()
            ->with(['creator', 'approver'])
            ->withSum('items as total_qty', 'quantity')
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->latest()
            ->paginate(15)
            ->appends($request->query());

        return view('admin.stock_receipts.index', [
            'receipts' => $receipts,
            'lowStock' => $this->stock->lowStockProducts(),
        ]);
    }

    public function create()
    {
        return view('admin.stock_receipts.create', [
            'products' => Product::query()->orderBy('name')->get(['id', 'name', 'sku', 'stock_quantity']),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'supplier_name'       => ['required', 'string', 'max:150'],
            'note'                => ['nullable', 'string', 'max:1000'],
            'serials'             => ['nullable', 'string', 'max:20000'],
            'items'               => ['required', 'array', 'min:1'],
            'items.*.product_id'  => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity'    => ['required', 'integer', 'min:1', 'max:100000'],
            'items.*.unit_cost'   => ['required', 'numeric', 'min:0'],
        ], [
            'items.required' => 'Vui lòng thêm ít nhất một sản phẩm vào phiếu nhập.',
        ]);

        // ValidationException từ service (lệch số Serial) được Laravel tự chuyển về form kèm thông báo
        $receipt = $this->stock->create($request->user(), $data);

        return redirect()->route('admin.stock_receipts.show', $receipt)
            ->with('success', "Đã lập phiếu nhập {$receipt->receipt_code} (chờ Admin duyệt để cộng kho).");
    }

    public function show(StockReceipt $stock_receipt)
    {
        $stock_receipt->load(['items.product', 'creator', 'approver']);

        return view('admin.stock_receipts.show', ['receipt' => $stock_receipt]);
    }

    public function approve(Request $request, StockReceipt $stock_receipt)
    {
        try {
            $this->stock->approve($stock_receipt, $request->user());
        } catch (DomainException | AuthorizationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Đã duyệt phiếu {$stock_receipt->receipt_code}, tồn kho đã được cập nhật.");
    }

    public function cancel(Request $request, StockReceipt $stock_receipt)
    {
        try {
            $this->stock->cancel($stock_receipt, $request->user());
        } catch (DomainException | AuthorizationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Đã hủy phiếu {$stock_receipt->receipt_code}.");
    }
}
