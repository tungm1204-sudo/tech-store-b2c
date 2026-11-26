<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WarrantyRequest;
use App\Services\WarrantyService;
use DomainException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Nhân viên / Admin: tiếp nhận, kiểm tra, duyệt / từ chối yêu cầu RMA. (Phụ trách: Trung Anh)
 */
class WarrantyManagementController extends Controller
{
    public function __construct(private WarrantyService $rma)
    {
    }

    public function index(Request $request)
    {
        $requests = WarrantyRequest::query()
            ->with(['user', 'order', 'orderItem'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->input('status')))
            ->when($request->filled('q'), function ($q) use ($request) {
                $kw = trim($request->input('q'));
                $q->where(function ($w) use ($kw) {
                    $w->where('rma_code', 'like', "%{$kw}%")->orWhere('serial_number', 'like', "%{$kw}%");
                });
            })
            ->latest()
            ->paginate(15)
            ->appends($request->query());

        $counts = WarrantyRequest::query()->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('admin.warranties.index', [
            'requests' => $requests,
            'counts'   => $counts,
            'statuses' => WarrantyRequest::STATUS_LABELS,
        ]);
    }

    public function show(Request $request, WarrantyRequest $warranty)
    {
        $warranty->load(['user', 'order', 'orderItem.product', 'handler']);

        $isAdmin = $request->user()->isAdmin();
        $next = collect($this->rma->allowedNext($warranty))
            ->reject(fn ($to) => ! $isAdmin && in_array($to, WarrantyService::ADMIN_ONLY_TARGETS, true))
            ->values()
            ->all();

        return view('admin.warranties.show', [
            'rma'      => $warranty,
            'next'     => $next,
            'actions'  => WarrantyService::ACTION_LABELS,
            'noteReq'  => WarrantyService::NOTE_REQUIRED_TARGETS,
            'isAdmin'  => $isAdmin,
        ]);
    }

    public function transition(Request $request, WarrantyRequest $warranty)
    {
        $data = $request->validate([
            'to'   => ['required', Rule::in(array_keys(WarrantyRequest::STATUS_LABELS))],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $this->rma->transition($warranty, $data['to'], $request->user(), $data['note'] ?? null);
        } catch (DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (AuthorizationException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', "Yêu cầu {$warranty->rma_code}: đã chuyển sang \"" . WarrantyRequest::STATUS_LABELS[$data['to']] . '".');
    }
}
