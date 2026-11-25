<?php

namespace App\Http\Controllers;

use App\Models\OrderItem;
use App\Models\WarrantyRequest;
use App\Services\WarrantyService;
use DomainException;
use Illuminate\Http\Request;

/**
 * Khách hàng: gửi & theo dõi yêu cầu Đổi trả / Bảo hành. (Phụ trách: Trung Anh)
 */
class RMAController extends Controller
{
    public function __construct(private WarrantyService $rma)
    {
    }

    public function index(Request $request)
    {
        $requests = WarrantyRequest::query()
            ->where('user_id', $request->user()->id)
            ->with(['order', 'orderItem'])
            ->latest()
            ->paginate(10);

        return view('warranties.index', ['requests' => $requests]);
    }

    public function create(Request $request)
    {
        $items = OrderItem::query()
            ->whereHas('order', fn ($q) => $q->where('user_id', $request->user()->id)->where('status', 'delivered'))
            ->with(['order', 'product'])
            ->latest('id')
            ->get()
            ->filter(fn (OrderItem $i) => $this->rma->remainingUnits($i) > 0)
            ->values();

        return view('warranties.create', [
            'items'    => $items,
            'selected' => (int) $request->query('order_item', 0),
            'service'  => $this->rma,
        ]);
    }

    public function store(Request $request)
    {
        $maxKb = (int) config('techstore.rma_evidence_max_kb', 20480);

        $data = $request->validate([
            'order_item_id'     => ['required', 'integer'],
            'serial_number'     => ['required', 'string', 'max:100'],
            'request_type'      => ['required', 'in:warranty,return'],
            'issue_description' => ['required', 'string', 'min:10', 'max:2000'],
            'evidence'          => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,mp4,mov,webm', "max:{$maxKb}"],
        ], [
            'issue_description.min' => 'Vui lòng mô tả lỗi chi tiết hơn (tối thiểu 10 ký tự).',
            'evidence.mimes'        => 'Minh chứng phải là ảnh (jpg, png, webp) hoặc video (mp4, mov, webm).',
            'evidence.max'          => 'Tệp minh chứng quá lớn (tối đa ' . round($maxKb / 1024) . ' MB).',
        ]);

        try {
            $rma = $this->rma->createRequest($request->user(), $data, $request->file('evidence'));
        } catch (DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('warranties.show', $rma)
            ->with('success', "Đã gửi yêu cầu {$rma->rma_code}. Nhân viên kỹ thuật sẽ tiếp nhận sớm nhất.");
    }

    public function show(Request $request, WarrantyRequest $warranty)
    {
        abort_unless($warranty->user_id === $request->user()->id, 404);

        $warranty->load(['order', 'orderItem.product', 'handler']);

        return view('warranties.show', ['rma' => $warranty]);
    }
}
