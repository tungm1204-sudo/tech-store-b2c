<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Quản lý mã giảm giá (Phúc) - chỉ Admin.
 */
class CouponController extends Controller
{
    public function index(Request $request)
    {
        $coupons = Coupon::query()
            ->when($request->filled('q'), fn($q) => $q->where('code', 'like', '%' . strtoupper(trim($request->q)) . '%'))
            ->latest('id')
            ->paginate(15)
            ->appends($request->query());

        return view('admin.coupons.index', compact('coupons'));
    }

    public function create()
    {
        return view('admin.coupons.create', ['coupon' => new Coupon([
            'discount_type' => 'percent',
            'min_order_amount' => 0,
            'max_uses' => 100,
            'is_active' => true,
        ])]);
    }

    public function store(Request $request)
    {
        Coupon::create($this->validated($request));

        return redirect()->route('admin.coupons.index')->with('success', 'Đã tạo mã giảm giá.');
    }

    public function edit(Coupon $coupon)
    {
        return view('admin.coupons.edit', compact('coupon'));
    }

    public function update(Request $request, Coupon $coupon)
    {
        $coupon->update($this->validated($request, $coupon));

        return redirect()->route('admin.coupons.index')->with('success', 'Đã cập nhật mã giảm giá.');
    }

    public function destroy(Coupon $coupon)
    {
        // Mã đã dùng trong đơn hàng thì chỉ khóa, không xóa để giữ lịch sử.
        if ($coupon->orders()->exists()) {
            $coupon->update(['is_active' => false]);

            return redirect()->route('admin.coupons.index')
                ->with('success', 'Mã đã được dùng trong đơn hàng nên chỉ bị khóa, không xóa.');
        }

        $coupon->delete();

        return redirect()->route('admin.coupons.index')->with('success', 'Đã xóa mã giảm giá.');
    }

    private function validated(Request $request, ?Coupon $coupon = null): array
    {
        $data = $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Za-z0-9_-]+$/',
                Rule::unique('coupons', 'code')->ignore($coupon?->id)
            ],
            'discount_type' => ['required', 'in:percent,fixed'],
            'discount_value' => [
                'required',
                'numeric',
                'gt:0',
                Rule::when($request->input('discount_type') === 'percent', ['max:100'])
            ],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'max_uses' => ['required', 'integer', 'min:1'],
            'expires_at' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'code.regex' => 'Mã chỉ gồm chữ, số, dấu gạch ngang hoặc gạch dưới.',
            'discount_value.max' => 'Giảm theo % không được vượt quá 100.',
        ]);

        $data['code'] = strtoupper($data['code']);
        $data['min_order_amount'] = $data['min_order_amount'] ?? 0;
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
