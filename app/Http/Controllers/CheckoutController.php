<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use App\Services\VietQrService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CheckoutController extends Controller
{
    private function ensureCustomer()
    {
        if (Auth::check()) {
            $user = Auth::user();

            if (!$user->is_active) {
                Auth::logout();
                request()->session()->invalidate();
                request()->session()->regenerateToken();

                if (Route::has('login')) {
                    return redirect()->route('login')->with('error', 'Tài khoản của bạn đã bị tạm khóa.');
                }

                return redirect()->route('home')->with('error', 'Tài khoản của bạn đã bị tạm khóa.');
            }

            abort_unless($user->role === 'customer', 403, 'Chỉ khách hàng mới có thể đặt hàng.');

            return null;
        }

        if (Route::has('login')) {
            return redirect()->guest(route('login'));
        }

        if (app()->environment('local') && Route::has('dev.login')) {
            return redirect()->route('dev.login', 'customer')
                ->with('info', 'Vui lòng đăng nhập tài khoản khách hàng để tiếp tục thanh toán.');
        }

        return redirect()->route('home')->with('error', 'Bạn cần đăng nhập để đặt hàng.');
    }

    /**
     * order_code có ràng buộc unique: thử lại cho đến khi chắc chắn không trùng.
     */
    private function generateOrderCode(): string
    {
        do {
            $code = 'TS-' . now()->format('Ymd') . '-' . strtoupper(Str::random(8));
        } while (Order::where('order_code', $code)->exists());

        return $code;
    }

    public function index(CartService $cartService)
    {
        if ($redirect = $this->ensureCustomer()) {
            return $redirect;
        }

        $cart = $cartService->getCart(true);
        if (!$cart || $cart->items->isEmpty()) {
            return redirect()->route('cart.index')->with('error', 'Giỏ hàng đang trống.');
        }

        $summary = $cartService->summary($cart);
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $addresses = $user->addresses()->get();

        return view('checkout.index', compact('cart', 'summary', 'addresses'))->with('cartService', $cartService);
    }

    public function store(Request $request, CartService $cartService)
    {
        if ($redirect = $this->ensureCustomer()) {
            return $redirect;
        }

        $data = $request->validate([
            'customer_name' => ['required', 'string', 'max:120'],
            'customer_phone' => ['required', 'string', 'max:25', 'regex:/^(?=(?:\D*\d){8,})[0-9+().\-\s]{8,25}$/'],
            'customer_email' => ['nullable', 'email', 'max:150'],
            'shipping_address' => ['required', 'string', 'max:1000'],
            'note' => ['nullable', 'string', 'max:2000'],
            'payment_method' => ['required', 'in:cod,qr_transfer'],
        ]);

        $user = Auth::user();
        $couponCode = session('coupon_code');
        if ($couponCode !== null && $couponCode !== '' && !$cartService->activeCoupon()) {
            // Giỏ hàng đã không còn hiển thị mã này nên không để nó chặn đơn hàng.
            session()->forget('coupon_code');
            $couponCode = null;
        }

        $order = DB::transaction(function () use ($data, $user, $couponCode, $cartService) {
            $cart = Cart::where('user_id', $user->id)->lockForUpdate()->first();

            // Nếu user vừa đăng nhập và giỏ còn đang gắn session, CartService sẽ nhập lại.
            if (!$cart) {
                $cart = app(CartService::class)->getCart(true);
                $cart = Cart::whereKey($cart->id)->lockForUpdate()->first();
            }

            $cartItems = $cart->items()->orderBy('product_id')->orderBy('id')->get();
            if ($cartItems->isEmpty()) {
                throw ValidationException::withMessages(['cart' => 'Giỏ hàng đang trống.']);
            }

            $productIds = $cartItems->pluck('product_id')->unique()->sort()->values();
            $products = Product::whereIn('id', $productIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // Khóa tất cả biến thể thuộc sản phẩm trong giỏ để không bỏ sót tồn kho biến thể.
            $variants = ProductVariant::whereIn('product_id', $productIds)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $variantsByProduct = $variants->groupBy('product_id');

            $prepared = [];
            $totalByProduct = [];
            $totalByVariant = [];
            $subtotal = 0.0;

            foreach ($cartItems as $cartItem) {
                $product = $products->get($cartItem->product_id);
                if (!$product || !$product->is_active) {
                    throw ValidationException::withMessages([
                        'cart' => 'Một sản phẩm trong giỏ đã ngừng kinh doanh. Vui lòng cập nhật giỏ hàng.',
                    ]);
                }

                $variant = null;
                $productVariants = $variantsByProduct->get($product->id, collect());
                if ($cartItem->variant_id) {
                    $variant = $variants->get($cartItem->variant_id);
                    if (!$variant || (int) $variant->product_id !== (int) $product->id) {
                        throw ValidationException::withMessages(['cart' => 'Biến thể sản phẩm không còn hợp lệ.']);
                    }
                } elseif ($productVariants->isNotEmpty()) {
                    throw ValidationException::withMessages([
                        'cart' => "Vui lòng chọn phiên bản cho sản phẩm {$product->name} trước khi thanh toán.",
                    ]);
                }

                $qty = (int) $cartItem->quantity;
                $totalByProduct[$product->id] = ($totalByProduct[$product->id] ?? 0) + $qty;
                if ($variant) {
                    $totalByVariant[$variant->id] = ($totalByVariant[$variant->id] ?? 0) + $qty;
                }

                $unitPrice = max(0, (float) $product->effective_price + ($variant ? (float) $variant->price_adjustment : 0));
                $lineTotal = $unitPrice * $qty;
                $subtotal += $lineTotal;

                $prepared[] = [
                    'product' => $product,
                    'variant' => $variant,
                    'quantity' => $qty,
                    'unit_price' => $unitPrice,
                    'line_total' => $lineTotal,
                    'variant_info' => $variant ? $variant->variant_name : null,
                ];
            }

            foreach ($totalByVariant as $variantId => $qty) {
                $variant = $variants->get($variantId);
                if (!$variant || (int) $variant->stock_quantity < $qty) {
                    throw ValidationException::withMessages([
                        'cart' => 'Một phiên bản sản phẩm không đủ tồn kho. Vui lòng cập nhật giỏ hàng.',
                    ]);
                }
            }

            // products.stock_quantity là tổng tồn kho; khóa và kiểm tra trước khi giảm.
            foreach ($totalByProduct as $productId => $qty) {
                $product = $products->get($productId);
                if ((int) $product->stock_quantity < $qty) {
                    throw ValidationException::withMessages([
                        'cart' => "Sản phẩm {$product->name} không đủ tồn kho. Còn {$product->stock_quantity} sản phẩm.",
                    ]);
                }
            }

            $coupon = null;
            $discount = 0.0;
            if ($couponCode !== null && $couponCode !== '') {
                $coupon = Coupon::where('code', $couponCode)->lockForUpdate()->first();
                if (!$coupon || !$coupon->isValidForSubtotal($subtotal)) {
                    throw ValidationException::withMessages([
                        'coupon_code' => 'Mã giảm giá đã hết hạn, hết lượt hoặc không còn đủ điều kiện áp dụng.',
                    ]);
                }
                $discount = $coupon->calculateDiscount($subtotal);
            }

            $shipping = $cartService->shippingFee($subtotal - $discount);
            $total = max(0, $subtotal - $discount + $shipping);

            $order = Order::create([
                'order_code' => $this->generateOrderCode(),
                'user_id' => $user->id,
                'coupon_id' => $coupon ? $coupon->id : null,
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'customer_email' => $data['customer_email'] ?? $user->email,
                'shipping_address' => $data['shipping_address'],
                'note' => $data['note'] ?? null,
                'subtotal' => $subtotal,
                'discount_amount' => $discount,
                'shipping_fee' => $shipping,
                'total_amount' => $total,
                'payment_method' => $data['payment_method'],
                'payment_status' => 'unpaid',
                'status' => 'pending',
                'stock_deducted_at' => now(),
            ]);

            foreach ($prepared as $line) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $line['product']->id,
                    'variant_id' => $line['variant'] ? $line['variant']->id : null,
                    'product_name' => $line['product']->name,
                    'variant_info' => $line['variant_info'],
                    'unit_price' => $line['unit_price'],
                    'quantity' => $line['quantity'],
                    'line_total' => $line['line_total'],
                ]);
            }

            // Trừ kho ngay lúc chốt đơn. Product được khóa trước nên hai phiên đặt hàng
            // không thể cùng tiêu thụ một số tồn kho.
            foreach ($totalByProduct as $productId => $qty) {
                $product = $products->get($productId);
                $product->stock_quantity = (int) $product->stock_quantity - $qty;
                $product->sold_count = (int) $product->sold_count + $qty;
                $product->save();
            }

            foreach ($totalByVariant as $variantId => $qty) {
                $variant = $variants->get($variantId);
                $variant->stock_quantity = (int) $variant->stock_quantity - $qty;
                $variant->save();
            }

            if ($coupon) {
                $coupon->used_count = (int) $coupon->used_count + 1;
                $coupon->save();
            }

            $cart->items()->delete();

            return $order;
        }, 3);

        session()->forget('coupon_code');

        if ($order->payment_method === 'qr_transfer') {
            return redirect()->route('checkout.payment', $order);
        }

        return redirect()->route('checkout.result', $order)
            ->with('success', 'Đặt hàng COD thành công. Đơn hàng đang chờ cửa hàng xác nhận.');
    }

    public function payment(Order $order, VietQrService $vietQr)
    {
        if ($redirect = $this->ensureCustomer()) {
            return $redirect;
        }
        abort_unless((int) $order->user_id === (int) Auth::id(), 404);
        abort_unless($order->payment_method === 'qr_transfer', 404);

        if ($order->status === 'cancelled') {
            return redirect()->route('checkout.result', $order)
                ->with('error', 'Đơn hàng đã bị hủy nên không thể tiếp tục thanh toán.');
        }

        if ($order->payment_status !== 'unpaid') {
            return redirect()->route('checkout.result', $order);
        }

        $qrUrl = $vietQr->imageUrl($order);

        return view('checkout.payment', compact('order', 'qrUrl'));
    }

    public function result(Order $order)
    {
        if ($redirect = $this->ensureCustomer()) {
            return $redirect;
        }
        abort_unless((int) $order->user_id === (int) Auth::id(), 404);

        return view('checkout.result', compact('order'));
    }

    /**
     * Nút xác nhận thanh toán giả lập chỉ được bật trong APP_ENV=local.
     * Môi trường thật cần webhook/API đối soát ngân hàng có chữ ký.
     */
    public function mockPaid(Order $order)
    {
        abort_unless(app()->environment('local'), 404);
        if ($redirect = $this->ensureCustomer()) {
            return $redirect;
        }
        abort_unless((int) $order->user_id === (int) Auth::id(), 404);

        DB::transaction(function () use ($order) {
            $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->payment_method === 'qr_transfer', 404);
            abort_unless($locked->status !== 'cancelled', 409);
            abort_unless($locked->payment_status !== 'refunded', 409);

            if ($locked->payment_status !== 'paid') {
                $locked->payment_status = 'paid';
                $locked->save();
            }
        }, 3);

        return redirect()->route('checkout.result', $order)
            ->with('success', '[DEMO] Đã giả lập xác nhận thanh toán QR.');
    }
}
