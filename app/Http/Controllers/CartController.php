<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function index(CartService $cartService)
    {
        $cart = $cartService->getCart(true);
        $summary = $cartService->summary($cart);

        return view('cart.index', compact('cart', 'summary'))->with('cartService', $cartService);
    }

    public function add(Request $request, CartService $cartService)
    {
        $data = $request->validate([
            'product_id' => ['required', 'integer', 'exists:products,id'],
            'variant_id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:99'],
        ]);

        $cartService->add(
            (int) $data['product_id'],
            (int) ($data['quantity'] ?? 1),
            isset($data['variant_id']) ? (int) $data['variant_id'] : null
        );

        return redirect()->route('cart.index')->with('success', 'Đã thêm sản phẩm vào giỏ hàng.');
    }

    public function update(Request $request, int $item, CartService $cartService)
    {
        $data = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:99'],
        ]);

        $cartService->update($item, (int) $data['quantity']);

        return redirect()->route('cart.index')->with('success', 'Đã cập nhật số lượng sản phẩm.');
    }

    public function remove(int $item, CartService $cartService)
    {
        $cartService->remove($item);

        return redirect()->route('cart.index')->with('success', 'Đã xóa sản phẩm khỏi giỏ hàng.');
    }

    public function applyCoupon(Request $request, CartService $cartService)
    {
        $data = $request->validate([
            'coupon_code' => ['required', 'string', 'max:50'],
        ]);

        $coupon = $cartService->applyCoupon($data['coupon_code']);

        return redirect()->route('cart.index')->with('success', "Đã áp dụng mã {$coupon->code}.");
    }

    public function removeCoupon(CartService $cartService)
    {
        session()->forget('coupon_code');

        return redirect()->route('cart.index')->with('success', 'Đã bỏ mã giảm giá.');
    }
}
