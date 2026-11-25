<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

/**
 * Nghiệp vụ giỏ hàng và mã giảm giá - Phúc.
 *
 * Giỏ hàng của khách chưa đăng nhập gắn với session_id.
 * Khi đăng nhập, giỏ khách được gộp vào giỏ của tài khoản.
 */
class CartService
{
    /** Ngưỡng freeship tính trên giá trị sau giảm giá. */
    public const FREE_SHIPPING_THRESHOLD = 5000000.0;

    /** Phí vận chuyển mặc định khi chưa đạt ngưỡng freeship. */
    public const SHIPPING_FEE = 30000.0;

    public function getCart(bool $create = true): ?Cart
    {
        $sessionId = Session::getId();

        if (Auth::check()) {
            $userId = Auth::id();
            $userCart = Cart::where('user_id', $userId)->first();

            // guest_cart_id survives Laravel session regeneration during login.
            $guestCartQuery = Cart::whereNull('user_id');
            $guestCartId = Session::get('guest_cart_id');
            if ($guestCartId) {
                $guestCartQuery->whereKey($guestCartId);
            } else {
                $guestCartQuery->where('session_id', $sessionId);
            }
            $guestCart = $guestCartQuery->first();
            if (!$guestCart && $guestCartId) {
                $guestCart = Cart::whereNull('user_id')->where('session_id', $sessionId)->first();
            }

            if (!$userCart && !$create && !$guestCart) {
                return null;
            }

            if (!$userCart && $guestCart) {
                $guestCart->user_id = $userId;
                $guestCart->session_id = $sessionId;
                $guestCart->save();
                $userCart = $guestCart;
                Session::forget('guest_cart_id');
            } elseif (!$userCart && $create) {
                $userCart = Cart::create([
                    'user_id' => $userId,
                    'session_id' => $sessionId,
                ]);
                Session::forget('guest_cart_id');
            }

            if ($userCart && $guestCart && $guestCart->id !== $userCart->id) {
                $this->mergeCarts($guestCart, $userCart);
                Session::forget('guest_cart_id');
            }

            return $userCart ? $userCart->load('items.product', 'items.variant') : null;
        }

        $guestCartId = Session::get('guest_cart_id');
        $cart = $guestCartId
            ? Cart::whereNull('user_id')->whereKey($guestCartId)->first()
            : null;

        if (!$cart) {
            $cart = Cart::whereNull('user_id')->where('session_id', $sessionId)->first();
        }

        if (!$cart && $create) {
            $cart = Cart::create(['session_id' => $sessionId]);
        }

        if ($cart) {
            Session::put('guest_cart_id', $cart->id);
        }

        return $cart ? $cart->load('items.product', 'items.variant') : null;
    }

    private function mergeCarts(Cart $source, Cart $target): void
    {
        DB::transaction(function () use ($source, $target) {
            $cartIds = [$source->id, $target->id];
            sort($cartIds);
            Cart::whereIn('id', $cartIds)->orderBy('id')->lockForUpdate()->get();

            foreach ($source->items()->lockForUpdate()->get() as $sourceItem) {
                $targetItem = $target->items()
                    ->where('product_id', $sourceItem->product_id)
                    ->where(function ($query) use ($sourceItem) {
                        if ($sourceItem->variant_id) {
                            $query->where('variant_id', $sourceItem->variant_id);
                        } else {
                            $query->whereNull('variant_id');
                        }
                    })->first();

                if ($targetItem) {
                    $targetItem->quantity += $sourceItem->quantity;
                    $targetItem->save();
                } else {
                    $target->items()->create([
                        'product_id' => $sourceItem->product_id,
                        'variant_id' => $sourceItem->variant_id,
                        'quantity' => $sourceItem->quantity,
                    ]);
                }
            }

            $source->delete();
        });
    }

    public function getCount(): int
    {
        $cart = $this->getCart(false);
        return $cart ? (int) $cart->items()->sum('quantity') : 0;
    }

    public function add(int $productId, int $quantity = 1, ?int $variantId = null): CartItem
    {
        return DB::transaction(function () use ($productId, $quantity, $variantId) {
            $cartRef = $this->getCart(true);
            $cart = Cart::whereKey($cartRef->id)->lockForUpdate()->firstOrFail();
            $product = Product::whereKey($productId)->lockForUpdate()->first();

            if (!$product || !$product->is_active) {
                throw ValidationException::withMessages(['product_id' => 'Sản phẩm không tồn tại hoặc đã ngừng kinh doanh.']);
            }

            $variant = null;
            if (!$variantId && ProductVariant::where('product_id', $product->id)->exists()) {
                throw ValidationException::withMessages(['variant_id' => 'Sản phẩm này có nhiều phiên bản. Vui lòng chọn phân loại trước khi thêm vào giỏ.']);
            }
            if ($variantId) {
                $variant = ProductVariant::whereKey($variantId)
                    ->where('product_id', $product->id)
                    ->lockForUpdate()
                    ->first();

                if (!$variant) {
                    throw ValidationException::withMessages(['variant_id' => 'Phiên bản sản phẩm không hợp lệ.']);
                }
            }

            $itemQuery = $cart->items()->where('product_id', $product->id);
            $variant ? $itemQuery->where('variant_id', $variant->id) : $itemQuery->whereNull('variant_id');
            $item = $itemQuery->lockForUpdate()->first();

            $newQuantity = ($item ? $item->quantity : 0) + $quantity;
            $available = $this->availableQuantity($cart, $product, $variant, $item?->id);

            if ($newQuantity > $available) {
                throw ValidationException::withMessages([
                    'quantity' => "Chỉ còn {$available} sản phẩm có thể chọn trong kho.",
                ]);
            }

            if ($item) {
                $item->quantity = $newQuantity;
                $item->save();
                return $item->fresh(['product', 'variant']);
            }

            return $cart->items()->create([
                'product_id' => $product->id,
                'variant_id' => $variant ? $variant->id : null,
                'quantity' => $quantity,
            ])->load('product', 'variant');
        });
    }

    public function update(int $itemId, int $quantity): CartItem
    {
        return DB::transaction(function () use ($itemId, $quantity) {
            $cartRef = $this->getCart(true);
            $cart = Cart::whereKey($cartRef->id)->lockForUpdate()->firstOrFail();
            $item = $cart->items()->whereKey($itemId)->lockForUpdate()->firstOrFail();
            $product = Product::whereKey($item->product_id)->lockForUpdate()->firstOrFail();
            $variant = $item->variant_id
                ? ProductVariant::whereKey($item->variant_id)->where('product_id', $product->id)->lockForUpdate()->first()
                : null;

            if ($quantity < 1) {
                throw ValidationException::withMessages(['quantity' => 'Số lượng phải lớn hơn hoặc bằng 1.']);
            }
            if (!$product->is_active || ($item->variant_id && !$variant)) {
                throw ValidationException::withMessages(['quantity' => 'Sản phẩm hoặc phân loại này không còn khả dụng.']);
            }
            if (!$item->variant_id && ProductVariant::where('product_id', $product->id)->exists()) {
                throw ValidationException::withMessages(['quantity' => 'Vui lòng chọn phân loại sản phẩm trước khi cập nhật.']);
            }

            $available = $this->availableQuantity($cart, $product, $variant, $item->id);

            if ($quantity > $available) {
                throw ValidationException::withMessages(['quantity' => "Kho chỉ còn {$available} sản phẩm."]);
            }

            $item->quantity = $quantity;
            $item->save();

            return $item->fresh(['product', 'variant']);
        }, 3);
    }

    public function remove(int $itemId): void
    {
        DB::transaction(function () use ($itemId) {
            $cartRef = $this->getCart(false);
            if (!$cartRef) {
                return;
            }
            $cart = Cart::whereKey($cartRef->id)->lockForUpdate()->first();
            if ($cart) {
                $cart->items()->whereKey($itemId)->delete();
            }
        }, 3);
    }

    public function clear(): void
    {
        DB::transaction(function () {
            $cartRef = $this->getCart(false);
            if ($cartRef) {
                $cart = Cart::whereKey($cartRef->id)->lockForUpdate()->first();
                if ($cart) {
                    $cart->items()->delete();
                }
            }
        }, 3);

        Session::forget('coupon_code');
    }

    public function applyCoupon(string $code): Coupon
    {
        $cart = $this->getCart(true);
        $subtotal = $this->subtotal($cart);
        if ($subtotal <= 0) {
            throw ValidationException::withMessages(['coupon_code' => 'Hãy thêm sản phẩm vào giỏ trước khi áp dụng mã.']);
        }

        $coupon = Coupon::where('code', strtoupper(trim($code)))->first();

        if (!$coupon || !$coupon->isValidForSubtotal($subtotal)) {
            throw ValidationException::withMessages([
                'coupon_code' => 'Mã giảm giá không hợp lệ, đã hết lượt, hết hạn hoặc chưa đạt giá trị đơn tối thiểu.',
            ]);
        }

        Session::put('coupon_code', $coupon->code);
        return $coupon;
    }

    public function activeCoupon(?float $subtotal = null): ?Coupon
    {
        $code = Session::get('coupon_code');
        if ($code === null || $code === '') {
            return null;
        }

        $coupon = Coupon::where('code', $code)->first();
        $subtotal = $subtotal ?? $this->subtotal($this->getCart(false));

        if (!$coupon || !$coupon->isValidForSubtotal($subtotal)) {
            return null;
        }

        return $coupon;
    }

    public function subtotal(?Cart $cart): float
    {
        if (!$cart) {
            return 0.0;
        }

        $cart->loadMissing('items.product', 'items.variant');
        return (float) $cart->items->sum(function (CartItem $item) {
            return $this->unitPrice($item) * (int) $item->quantity;
        });
    }

    public function unitPrice(CartItem $item): float
    {
        $base = $item->product ? (float) $item->product->effective_price : 0.0;
        $adjustment = $item->variant ? (float) $item->variant->price_adjustment : 0.0;
        return max(0, $base + $adjustment);
    }

    /**
     * Phí ship được tính từ một nguồn duy nhất để giỏ hàng và checkout
     * không bao giờ lệch nhau.
     */
    public function shippingFee(float $amountAfterDiscount): float
    {
        return $amountAfterDiscount >= self::FREE_SHIPPING_THRESHOLD ? 0.0 : self::SHIPPING_FEE;
    }

    /**
     * Số lượng tối đa có thể đặt cho một dòng giỏ hàng.
     *
     * products.stock_quantity là TỔNG tồn kho của mọi biến thể, nên ngoài việc
     * kiểm tra tồn của riêng biến thể phải trừ đi các dòng khác cùng sản phẩm.
     * Nếu không, khách có thể thêm 8GB + 8GB của cùng một sản phẩm dù tổng kho chỉ 10.
     */
    private function availableQuantity(
        Cart $cart,
        Product $product,
        ?ProductVariant $variant,
        ?int $excludeItemId = null
    ): int {
        $otherQuantity = (int) $cart->items()
            ->when($excludeItemId, fn ($query) => $query->whereKeyNot($excludeItemId))
            ->where('product_id', $product->id)
            ->sum('quantity');

        $productAvailable = max(0, (int) $product->stock_quantity - $otherQuantity);

        if (!$variant) {
            return $productAvailable;
        }

        return max(0, min((int) $variant->stock_quantity, $productAvailable));
    }

    public function summary(?Cart $cart): array
    {
        $cart = $cart ?: new Cart();
        $cart->loadMissing('items.product', 'items.variant');

        $subtotal = $this->subtotal($cart);
        $coupon = $this->activeCoupon($subtotal);
        $discount = $coupon ? $coupon->calculateDiscount($subtotal) : 0.0;
        $shipping = $subtotal > 0 ? $this->shippingFee($subtotal - $discount) : 0.0;

        return [
            'subtotal' => $subtotal,
            'coupon' => $coupon,
            'discount' => $discount,
            'shipping' => $shipping,
            'total' => max(0, $subtotal - $discount + $shipping),
            'count' => (int) $cart->items->sum('quantity'),
        ];
    }
}
