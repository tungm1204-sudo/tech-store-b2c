<?php

namespace App\Services;

use App\Models\OrderItem;
use App\Models\User;
use App\Models\WarrantyRequest;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Nghiệp vụ Đổi trả / Bảo hành thiết bị - RMA (Phụ trách: Trung Anh)
 *
 *   submitted (Yêu cầu mới)
 *      -> inspecting (Nhân viên kỹ thuật tiếp nhận & kiểm tra)
 *            -> approved (Quản lý duyệt đổi mới / sửa chữa)  -> completed (Đã hoàn tất)
 *            -> rejected (Từ chối)                           [kết thúc]
 */
class WarrantyService
{
    public const TRANSITIONS = [
        'submitted'  => ['inspecting'],
        'inspecting' => ['approved', 'rejected'],
        'approved'   => ['completed'],
        'rejected'   => [],
        'completed'  => [],
    ];

    /** Chỉ Quản lý (Admin) mới được quyết định duyệt / từ chối */
    public const ADMIN_ONLY_TARGETS = ['approved', 'rejected'];

    /** Bước đích bắt buộc phải có ghi chú xử lý */
    public const NOTE_REQUIRED_TARGETS = ['approved', 'rejected'];

    public const ACTION_LABELS = [
        'inspecting' => 'Tiếp nhận & Kiểm tra kỹ thuật',
        'approved'   => 'Duyệt (Đổi mới / Sửa chữa / Hoàn tiền)',
        'rejected'   => 'Từ chối',
        'completed'  => 'Hoàn tất quy trình',
    ];

    /** Các trạng thái còn "đang xử lý" - dùng để chặn gửi trùng yêu cầu */
    public const OPEN_STATUSES = ['submitted', 'inspecting', 'approved'];

    public function allowedNext(WarrantyRequest|string $rmaOrStatus): array
    {
        $status = $rmaOrStatus instanceof WarrantyRequest ? $rmaOrStatus->status : $rmaOrStatus;

        return self::TRANSITIONS[$status] ?? [];
    }

    /**
     * Số thiết bị của dòng hàng này còn có thể gửi yêu cầu
     * (mỗi thiết bị mua = 1 yêu cầu đang mở / đã duyệt; yêu cầu bị từ chối không chiếm suất).
     */
    public function remainingUnits(OrderItem $item): int
    {
        $used = WarrantyRequest::query()
            ->where('order_item_id', $item->id)
            ->whereIn('status', ['submitted', 'inspecting', 'approved', 'completed'])
            ->count();

        return max(0, (int) $item->quantity - $used);
    }

    /**
     * Kiểm tra điều kiện & tạo yêu cầu mới cho khách hàng.
     *
     * @param array{order_item_id:int|string, serial_number:string, request_type:string, issue_description:string} $data
     */
    public function createRequest(User $customer, array $data, ?UploadedFile $evidence = null): WarrantyRequest
    {
        $item = OrderItem::query()
            ->with(['order', 'product'])
            ->find($data['order_item_id']);

        // Không tồn tại hoặc không phải đơn của khách -> cùng một thông báo (tránh dò ID đơn của người khác)
        if (! $item || (int) $item->order->user_id !== (int) $customer->id) {
            throw new DomainException('Không tìm thấy sản phẩm trong đơn hàng của bạn.');
        }

        $order = $item->order;
        if ($order->status !== 'delivered' || ! $order->delivered_at) {
            throw new DomainException('Chỉ có thể gửi yêu cầu cho sản phẩm thuộc đơn hàng đã giao thành công.');
        }

        $type = $data['request_type'];
        $deliveredAt = $order->delivered_at;

        if ($type === 'return') {
            $days = (int) config('techstore.return_window_days', 30);
            if (now()->gt($deliveredAt->copy()->addDays($days))) {
                throw new DomainException("Đã quá thời hạn đổi trả {$days} ngày kể từ ngày nhận hàng.");
            }
        } else {
            $months = (int) ($item->product->warranty_months ?? 12);
            if (now()->gt($deliveredAt->copy()->addMonths($months))) {
                throw new DomainException("Sản phẩm đã hết hạn bảo hành {$months} tháng.");
            }
        }

        $serial = trim($data['serial_number']);

        $path = null;
        if ($evidence) {
            $path = $evidence->store('rma-evidence', 'public');
        }

        try {
            return $this->createWithRetry($customer, $item, $serial, $type, $data['issue_description'], $path);
        } catch (\Throwable $e) {
            if ($path) {
                Storage::disk('public')->delete($path);
            }
            throw $e;
        }
    }

    protected function createWithRetry(User $customer, OrderItem $item, string $serial, string $type, string $issue, ?string $path): WarrantyRequest
    {
        $attempts = 0;

        while (true) {
            try {
                return DB::transaction(function () use ($customer, $item, $serial, $type, $issue, $path) {
                    // Khóa dòng hàng để 2 yêu cầu gửi cùng lúc không vượt quá số lượng đã mua
                    OrderItem::query()->whereKey($item->id)->lockForUpdate()->first();

                    if ($this->remainingUnits($item) < 1) {
                        throw new DomainException('Tất cả thiết bị của dòng hàng này đã có yêu cầu bảo hành / đổi trả.');
                    }

                    $duplicate = WarrantyRequest::query()
                        ->where('order_item_id', $item->id)
                        ->where('serial_number', $serial)
                        ->whereIn('status', self::OPEN_STATUSES)
                        ->exists();
                    if ($duplicate) {
                        throw new DomainException("Mã Serial/IMEI {$serial} đã có yêu cầu đang được xử lý.");
                    }

                    return WarrantyRequest::create([
                        'rma_code'          => CodeGenerator::next('RMA', WarrantyRequest::class, 'rma_code'),
                        'order_id'          => $item->order_id,
                        'order_item_id'     => $item->id,
                        'user_id'           => $customer->id,
                        'serial_number'     => $serial,
                        'request_type'      => $type,
                        'issue_description' => $issue,
                        'evidence_url'      => $path ? Storage::disk('public')->url($path) : null,
                        'status'            => 'submitted',
                    ]);
                });
            } catch (\Illuminate\Database\QueryException $e) {
                // 23000 = trùng khóa unique (rma_code) do 2 người tạo cùng lúc -> thử lại với mã mới
                if (($e->errorInfo[0] ?? null) === '23000' && ++$attempts < 5) {
                    continue;
                }
                throw $e;
            }
        }
    }

    /**
     * Chuyển trạng thái xử lý yêu cầu.
     *
     * @throws DomainException|AuthorizationException
     */
    public function transition(WarrantyRequest $rma, string $to, User $actor, ?string $note = null): WarrantyRequest
    {
        if (in_array($to, self::ADMIN_ONLY_TARGETS, true) && ! $actor->isAdmin()) {
            throw new AuthorizationException('Chỉ Quản lý (Admin) mới có quyền duyệt hoặc từ chối yêu cầu bảo hành.');
        }
        if (! $actor->isStaffOrAdmin()) {
            throw new AuthorizationException('Bạn không có quyền xử lý yêu cầu bảo hành.');
        }

        $note = $note !== null ? trim($note) : null;
        if (in_array($to, self::NOTE_REQUIRED_TARGETS, true) && ($note === null || $note === '')) {
            throw new DomainException('Vui lòng nhập ghi chú / phương án xử lý (đổi mới, sửa chữa, hoàn tiền hoặc lý do từ chối).');
        }

        return DB::transaction(function () use ($rma, $to, $actor, $note) {
            /** @var WarrantyRequest $locked */
            $locked = WarrantyRequest::query()->whereKey($rma->getKey())->lockForUpdate()->firstOrFail();

            if (! in_array($to, self::TRANSITIONS[$locked->status] ?? [], true)) {
                $from = WarrantyRequest::STATUS_LABELS[$locked->status] ?? $locked->status;
                $target = WarrantyRequest::STATUS_LABELS[$to] ?? $to;
                throw new DomainException("Không thể chuyển yêu cầu {$locked->rma_code} từ \"{$from}\" sang \"{$target}\".");
            }

            $attrs = ['status' => $to, 'handled_by' => $actor->id];
            if ($note !== null && $note !== '') {
                $attrs['resolution_note'] = $note;
            }

            $locked->update($attrs);

            return $locked->refresh();
        });
    }
}
