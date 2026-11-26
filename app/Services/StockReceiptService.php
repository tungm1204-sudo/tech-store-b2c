<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockReceipt;
use App\Models\StockReceiptItem;
use App\Models\User;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Nhập kho & Quản lý tồn kho (Phụ trách: Trung Anh)
 *
 * Quy trình: Lập phiếu nhập (pending) -> kiểm kê IMEI/Serial -> Admin duyệt (approved)
 *            => tồn kho sản phẩm được cộng tự động; sản phẩm sắp hết hàng được cảnh báo.
 */
class StockReceiptService
{
    /**
     * Tạo phiếu nhập kho (trạng thái pending - chưa cộng kho).
     *
     * @param array{supplier_name:string, note?:?string, serials?:?string, items:array<int,array{product_id:int|string, quantity:int|string, unit_cost:int|float|string}>} $data
     */
    public function create(User $creator, array $data): StockReceipt
    {
        // Gộp các dòng trùng sản phẩm
        $lines = [];
        foreach ($data['items'] as $row) {
            $pid = (int) $row['product_id'];
            $qty = (int) $row['quantity'];
            $cost = (float) $row['unit_cost'];

            if (isset($lines[$pid])) {
                // Giá nhập bình quân gia quyền khi gộp dòng
                $total = $lines[$pid]['quantity'] + $qty;
                $lines[$pid]['unit_cost'] = round(
                    ($lines[$pid]['unit_cost'] * $lines[$pid]['quantity'] + $cost * $qty) / $total,
                    2
                );
                $lines[$pid]['quantity'] = $total;
            } else {
                $lines[$pid] = ['product_id' => $pid, 'quantity' => $qty, 'unit_cost' => $cost];
            }
        }

        $totalQty = array_sum(array_column($lines, 'quantity'));
        $totalCost = array_sum(array_map(fn ($l) => $l['quantity'] * $l['unit_cost'], $lines));

        // Kiểm kê Serial/IMEI: nếu có nhập danh sách thì phải khớp đúng tổng số lượng
        $serials = $this->parseSerials($data['serials'] ?? null);
        if ($serials->isNotEmpty() && $serials->count() !== $totalQty) {
            throw ValidationException::withMessages([
                'serials' => "Số mã Serial/IMEI ({$serials->count()}) không khớp tổng số lượng nhập ({$totalQty}). Vui lòng kiểm kê lại.",
            ]);
        }

        $noteParts = [];
        if (! empty(trim((string) ($data['note'] ?? '')))) {
            $noteParts[] = trim($data['note']);
        }
        if ($serials->isNotEmpty()) {
            $noteParts[] = 'Serial/IMEI (' . $serials->count() . '): ' . $serials->implode(', ');
        }

        return DB::transaction(function () use ($creator, $data, $lines, $totalCost, $noteParts) {
            $receipt = $this->makeReceipt($creator, $data['supplier_name'], $noteParts, $totalCost);

            foreach ($lines as $line) {
                StockReceiptItem::create($line + ['stock_receipt_id' => $receipt->id]);
            }

            return $receipt;
        });
    }

    private function makeReceipt(User $creator, string $supplier, array $noteParts, float $totalCost): StockReceipt
    {
        // Chạy trong transaction của create(): CodeGenerator khóa dòng mã mới nhất nên 2 phiếu cùng lúc không trùng mã.
        return StockReceipt::create([
            'receipt_code'    => CodeGenerator::next('STK', StockReceipt::class, 'receipt_code'),
            'supplier_name'   => trim($supplier),
            'imei_batch_note' => $noteParts ? implode("\n", $noteParts) : null,
            'created_by'      => $creator->id,
            'total_cost'      => $totalCost,
            'status'          => 'pending',
        ]);
    }

    /** Admin duyệt phiếu -> cộng tồn kho tự động */
    public function approve(StockReceipt $receipt, User $approver): StockReceipt
    {
        if (! $approver->isAdmin()) {
            throw new AuthorizationException('Chỉ Quản trị viên mới được duyệt phiếu nhập kho.');
        }

        return DB::transaction(function () use ($receipt, $approver) {
            /** @var StockReceipt $locked */
            $locked = StockReceipt::query()->whereKey($receipt->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->status !== 'pending') {
                throw new DomainException("Phiếu {$locked->receipt_code} không ở trạng thái chờ duyệt.");
            }

            foreach ($locked->items()->get() as $item) {
                Product::query()->whereKey($item->product_id)->increment('stock_quantity', $item->quantity);
            }

            $locked->update([
                'status'      => 'approved',
                'approved_by' => $approver->id,
                'approved_at' => now(),
            ]);

            return $locked->refresh();
        });
    }

    /** Hủy phiếu đang chờ duyệt (không ảnh hưởng tồn kho) */
    public function cancel(StockReceipt $receipt, User $actor): StockReceipt
    {
        if (! $actor->isStaffOrAdmin()) {
            throw new AuthorizationException('Bạn không có quyền hủy phiếu nhập kho.');
        }

        return DB::transaction(function () use ($receipt) {
            $locked = StockReceipt::query()->whereKey($receipt->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->status !== 'pending') {
                throw new DomainException('Chỉ có thể hủy phiếu đang chờ duyệt.');
            }

            $locked->update(['status' => 'cancelled']);

            return $locked->refresh();
        });
    }

    /** Sản phẩm sắp hết hàng (tồn <= ngưỡng cảnh báo), ít hàng nhất lên đầu */
    public function lowStockProducts(): Collection
    {
        return Product::query()
            ->where('is_active', true)
            ->whereColumn('stock_quantity', '<=', 'low_stock_threshold')
            ->orderBy('stock_quantity')
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'stock_quantity', 'low_stock_threshold']);
    }

    /** Tách danh sách Serial/IMEI (mỗi dòng hoặc ngăn cách bằng , ;) và loại trùng không phân biệt hoa thường */
    public function parseSerials(?string $raw): Collection
    {
        if ($raw === null || trim($raw) === '') {
            return collect();
        }

        return collect(preg_split('/[\r\n,;]+/', $raw))
            ->map(fn ($s) => trim($s))
            ->filter(fn ($s) => $s !== '')
            ->unique(fn ($s) => mb_strtolower($s))
            ->values();
    }
}
