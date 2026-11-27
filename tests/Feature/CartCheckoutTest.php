<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Kiểm thử module Giỏ hàng & Thanh toán (Phúc).
 * Chạy: php artisan test --filter=CartCheckoutTest
 * Dùng SQLite in-memory (xem phpunit.xml) nên không đụng tới database MySQL thật.
 */
class CartCheckoutTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Ép test này dùng SQLite in-memory để RefreshDatabase KHÔNG xóa database MySQL thật
     * (không sửa phpunit.xml vì ModelsAuditTest của Tự cần MySQL đã seed).
     */
    protected function refreshApplication()
    {
        parent::refreshApplication();
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
    }

    private function makeProduct(array $attrs = [], array $variants = []): Product
    {
        $cat = Category::firstOrCreate(['slug' => 'laptop'], ['name' => 'Laptop']);
        $brand = Brand::firstOrCreate(['slug' => 'dell'], ['name' => 'Dell']);

        $product = Product::create($attrs + [
            'category_id' => $cat->id,
            'brand_id' => $brand->id,
            'sku' => 'SKU-' . uniqid(),
            'name' => 'Laptop Test',
            'slug' => 'laptop-test-' . uniqid(),
            'price' => 10000000,
            'stock_quantity' => 5,
        ]);

        foreach ($variants as $v) {
            ProductVariant::create($v + ['product_id' => $product->id, 'sku_suffix' => 'X']);
        }

        return $product;
    }

    private function customer(): User
    {
        return User::create([
            'name' => 'Khach Test',
            'email' => 'c' . uniqid() . '@test.vn',
            'password' => bcrypt('password'),
            'phone' => '0900000000',
            'address' => 'Ha Noi',
            'role' => 'customer',
            'is_active' => true,
        ]);
    }

    private function checkoutPayload(string $method = 'cod'): array
    {
        return [
            'customer_name' => 'Nguyen Van A',
            'customer_phone' => '0912345678',
            'shipping_address' => '1 Tran Duy Hung, Ha Noi',
            'payment_method' => $method,
        ];
    }

    public function test_guest_can_add_to_cart_and_sees_count(): void
    {
        $p = $this->makeProduct();

        $this->post(route('cart.add'), ['product_id' => $p->id, 'quantity' => 2])
            ->assertRedirect(route('cart.index'));

        $this->get(route('cart.index'))->assertOk()->assertSee('Laptop Test');
        $this->assertDatabaseHas('cart_items', ['product_id' => $p->id, 'quantity' => 2]);
    }

    public function test_adding_same_product_twice_merges_quantity(): void
    {
        $p = $this->makeProduct();
        $this->post(route('cart.add'), ['product_id' => $p->id, 'quantity' => 1]);
        $this->post(route('cart.add'), ['product_id' => $p->id, 'quantity' => 2]);

        $this->assertDatabaseCount('cart_items', 1);
        $this->assertDatabaseHas('cart_items', ['product_id' => $p->id, 'quantity' => 3]);
    }

    public function test_cannot_add_more_than_stock(): void
    {
        $p = $this->makeProduct(['stock_quantity' => 2]);

        $this->post(route('cart.add'), ['product_id' => $p->id, 'quantity' => 3])
            ->assertSessionHasErrors('quantity');
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_product_with_variants_requires_variant(): void
    {
        $p = $this->makeProduct([], [['variant_name' => '16GB', 'stock_quantity' => 3]]);

        $this->post(route('cart.add'), ['product_id' => $p->id, 'quantity' => 1])
            ->assertSessionHasErrors('variant_id');
    }

    public function test_variant_price_adjustment_is_applied(): void
    {
        $p = $this->makeProduct([], [['variant_name' => '32GB', 'stock_quantity' => 3, 'price_adjustment' => 2000000]]);
        $v = $p->variants()->first();

        $this->post(route('cart.add'), ['product_id' => $p->id, 'variant_id' => $v->id, 'quantity' => 1]);

        $this->get(route('cart.index'))->assertOk()->assertSee('12.000.000');
    }

    public function test_update_and_remove_cart_item(): void
    {
        $p = $this->makeProduct();
        $this->post(route('cart.add'), ['product_id' => $p->id, 'quantity' => 1]);
        $item = \App\Models\CartItem::first();

        $this->patch(route('cart.update', $item->id), ['quantity' => 4])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('cart_items', ['id' => $item->id, 'quantity' => 4]);

        $this->patch(route('cart.update', $item->id), ['quantity' => 9])->assertSessionHasErrors('quantity');

        $this->delete(route('cart.remove', $item->id));
        $this->assertDatabaseCount('cart_items', 0);
    }

    public function test_cart_respects_total_stock_across_variants(): void
    {
        // Tổng kho 10 nhưng mỗi biến thể có 8: không được cộng 8 + 8 vào giỏ.
        $p = $this->makeProduct(['stock_quantity' => 10], [
            ['variant_name' => '16GB', 'stock_quantity' => 8],
            ['variant_name' => '32GB', 'stock_quantity' => 8],
        ]);
        [$v1, $v2] = $p->variants()->orderBy('id')->get()->all();

        $this->post(route('cart.add'), ['product_id' => $p->id, 'variant_id' => $v1->id, 'quantity' => 8])
            ->assertSessionHasNoErrors();
        $this->post(route('cart.add'), ['product_id' => $p->id, 'variant_id' => $v2->id, 'quantity' => 8])
            ->assertSessionHasErrors('quantity');

        $this->assertDatabaseCount('cart_items', 1);
        $this->assertSame(8, (int) \App\Models\CartItem::first()->quantity);
    }

    public function test_update_respects_total_stock_across_variants(): void
    {
        $p = $this->makeProduct(['stock_quantity' => 10], [
            ['variant_name' => '16GB', 'stock_quantity' => 8],
            ['variant_name' => '32GB', 'stock_quantity' => 8],
        ]);
        [$v1, $v2] = $p->variants()->orderBy('id')->get()->all();

        $this->post(route('cart.add'), ['product_id' => $p->id, 'variant_id' => $v1->id, 'quantity' => 8]);
        $this->post(route('cart.add'), ['product_id' => $p->id, 'variant_id' => $v2->id, 'quantity' => 2]);
        $item = \App\Models\CartItem::where('variant_id', $v2->id)->first();

        $this->patch(route('cart.update', $item->id), ['quantity' => 3])->assertSessionHasErrors('quantity');
        $this->assertSame(2, (int) $item->fresh()->quantity);
    }

    public function test_user_cannot_modify_someone_elses_cart_item(): void
    {
        $p = $this->makeProduct();
        $this->post(route('cart.add'), ['product_id' => $p->id, 'quantity' => 1]);
        $item = \App\Models\CartItem::first();

        // Phiên khác (khách khác)
        $this->flushSession();
        $this->app['auth']->forgetGuards();
        $this->patch(route('cart.update', $item->id), ['quantity' => 2])->assertStatus(404);
        $this->delete(route('cart.remove', $item->id));
        $this->assertDatabaseHas('cart_items', ['id' => $item->id]);
    }

    public function test_coupon_rules(): void
    {
        $p = $this->makeProduct(['price' => 3000000]);
        Coupon::create(['code' => 'TECH10', 'discount_type' => 'percent', 'discount_value' => 10, 'min_order_amount' => 2000000, 'max_uses' => 5, 'used_count' => 0, 'is_active' => true]);
        Coupon::create(['code' => 'BIG', 'discount_type' => 'fixed', 'discount_value' => 500000, 'min_order_amount' => 10000000, 'max_uses' => 5, 'used_count' => 0, 'is_active' => true]);
        Coupon::create(['code' => 'OLD', 'discount_type' => 'percent', 'discount_value' => 10, 'min_order_amount' => 0, 'max_uses' => 5, 'used_count' => 0, 'expires_at' => now()->subDay(), 'is_active' => true]);
        Coupon::create(['code' => 'FULL', 'discount_type' => 'percent', 'discount_value' => 10, 'min_order_amount' => 0, 'max_uses' => 1, 'used_count' => 1, 'is_active' => true]);

        $this->post(route('cart.add'), ['product_id' => $p->id, 'quantity' => 1]);

        foreach (['BIG', 'OLD', 'FULL', 'NOPE'] as $bad) {
            $this->post(route('cart.coupon.apply'), ['coupon_code' => $bad])->assertSessionHasErrors('coupon_code');
        }

        $this->post(route('cart.coupon.apply'), ['coupon_code' => 'tech10'])->assertSessionHasNoErrors();
        $this->get(route('cart.index'))->assertSee('TECH10')->assertSee('300.000');
    }

    public function test_checkout_requires_login_and_non_empty_cart(): void
    {
        $p = $this->makeProduct();
        $this->post(route('cart.add'), ['product_id' => $p->id, 'quantity' => 1]);

        $this->get(route('checkout.index'))->assertRedirect(); // chưa đăng nhập -> bị chuyển hướng

        $this->actingAs($this->customer())->get(route('checkout.index'))->assertOk();
    }

    public function test_guest_cart_is_merged_after_login(): void
    {
        $p = $this->makeProduct();
        $this->post(route('cart.add'), ['product_id' => $p->id, 'quantity' => 2]);
        $user = $this->customer();

        $this->actingAs($user)->get(route('cart.index'))->assertOk();
        $this->assertDatabaseHas('carts', ['user_id' => $user->id]);
        $this->assertDatabaseHas('cart_items', ['product_id' => $p->id, 'quantity' => 2]);
    }

    public function test_cod_checkout_creates_order_and_deducts_stock(): void
    {
        $p = $this->makeProduct(['price' => 3000000, 'stock_quantity' => 5]);
        $user = $this->customer();
        Coupon::create(['code' => 'TECH10', 'discount_type' => 'percent', 'discount_value' => 10, 'min_order_amount' => 2000000, 'max_uses' => 5, 'used_count' => 0, 'is_active' => true]);

        $this->actingAs($user);
        $this->post(route('cart.add'), ['product_id' => $p->id, 'quantity' => 2]);
        $this->post(route('cart.coupon.apply'), ['coupon_code' => 'TECH10']);

        $this->post(route('checkout.store'), $this->checkoutPayload('cod'))->assertSessionHasNoErrors();

        $order = Order::first();
        $this->assertNotNull($order);
        $this->assertSame('pending', $order->status);
        $this->assertSame('unpaid', $order->payment_status);
        $this->assertEquals(6000000, (float) $order->subtotal);
        $this->assertEquals(600000, (float) $order->discount_amount);
        $this->assertEquals(0, (float) $order->shipping_fee); // 5.400.000 >= 5.000.000 nên miễn phí ship
        $this->assertEquals(5400000, (float) $order->total_amount);
        $this->assertDatabaseHas('order_items', ['order_id' => $order->id, 'quantity' => 2, 'line_total' => 6000000]);
        $this->assertSame(3, (int) $p->fresh()->stock_quantity);
        $this->assertSame(2, (int) $p->fresh()->sold_count);
        $this->assertSame(1, (int) Coupon::first()->used_count);
        $this->assertDatabaseCount('cart_items', 0);
        $this->get(route('checkout.result', $order))->assertOk()->assertSee($order->order_code);
    }

    public function test_free_shipping_when_total_at_least_5m(): void
    {
        $p = $this->makeProduct(['price' => 6000000]);
        $this->actingAs($this->customer());
        $this->post(route('cart.add'), ['product_id' => $p->id, 'quantity' => 1]);
        $this->post(route('checkout.store'), $this->checkoutPayload());

        $this->assertEquals(0, (float) Order::first()->shipping_fee);
    }

    public function test_qr_checkout_shows_qr_and_mock_paid(): void
    {
        config(['services.vietqr.bank_id' => 'MB', 'services.vietqr.account_no' => '0123456789', 'services.vietqr.account_name' => 'TECHSTORE']);
        $p = $this->makeProduct();
        $this->actingAs($this->customer());
        $this->post(route('cart.add'), ['product_id' => $p->id, 'quantity' => 1]);

        $res = $this->post(route('checkout.store'), $this->checkoutPayload('qr_transfer'));
        $order = Order::first();
        $res->assertRedirect(route('checkout.payment', $order));

        $this->get(route('checkout.payment', $order))->assertOk()->assertSee('img.vietqr.io');

        // Nút giả lập thanh toán chỉ hoạt động khi APP_ENV=local
        $this->post(route('checkout.mock-paid', $order))->assertNotFound();
        $this->app['env'] = 'local';
        $this->withoutMiddleware(\App\Http\Middleware\VerifyCsrfToken::class); // môi trường local không được miễn CSRF như testing
        $this->post(route('checkout.mock-paid', $order))->assertRedirect(route('checkout.result', $order));
        $this->assertSame('paid', $order->fresh()->payment_status);
    }

    public function test_checkout_rejects_when_stock_changed(): void
    {
        $p = $this->makeProduct(['stock_quantity' => 3]);
        $this->actingAs($this->customer());
        $this->post(route('cart.add'), ['product_id' => $p->id, 'quantity' => 3]);

        $p->update(['stock_quantity' => 1]); // người khác mua mất

        $this->post(route('checkout.store'), $this->checkoutPayload())->assertSessionHasErrors('cart');
        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(1, (int) $p->fresh()->stock_quantity);
    }

    public function test_variant_stock_is_deducted(): void
    {
        $p = $this->makeProduct(['stock_quantity' => 10], [['variant_name' => '16GB', 'stock_quantity' => 4]]);
        $v = $p->variants()->first();
        $this->actingAs($this->customer());
        $this->post(route('cart.add'), ['product_id' => $p->id, 'variant_id' => $v->id, 'quantity' => 3]);
        $this->post(route('checkout.store'), $this->checkoutPayload());

        $this->assertSame(1, (int) $v->fresh()->stock_quantity);
        $this->assertSame(7, (int) $p->fresh()->stock_quantity);
    }

    public function test_checkout_validation(): void
    {
        $p = $this->makeProduct();
        $this->actingAs($this->customer());
        $this->post(route('cart.add'), ['product_id' => $p->id, 'quantity' => 1]);

        $this->post(route('checkout.store'), ['payment_method' => 'bitcoin'])
            ->assertSessionHasErrors(['customer_name', 'customer_phone', 'shipping_address', 'payment_method']);
    }

    public function test_cancelling_order_restores_stock_once(): void
    {
        $p = $this->makeProduct(['stock_quantity' => 5]);
        $this->actingAs($this->customer());
        $this->post(route('cart.add'), ['product_id' => $p->id, 'quantity' => 2]);
        $this->post(route('checkout.store'), $this->checkoutPayload());
        $order = Order::first();
        $this->assertSame(3, (int) $p->fresh()->stock_quantity);

        $order->update(['status' => 'cancelled']);
        $this->assertSame(5, (int) $p->fresh()->stock_quantity);

        $order->update(['cancelled_reason' => 'x']);
        $order->update(['status' => 'cancelled']);
        $this->assertSame(5, (int) $p->fresh()->stock_quantity);
    }

    public function test_other_user_cannot_view_order(): void
    {
        $p = $this->makeProduct();
        $this->actingAs($this->customer());
        $this->post(route('cart.add'), ['product_id' => $p->id, 'quantity' => 1]);
        $this->post(route('checkout.store'), $this->checkoutPayload());
        $order = Order::first();

        $this->actingAs($this->customer())->get(route('checkout.result', $order))->assertNotFound();
    }

    public function test_stale_coupon_in_session_does_not_block_checkout(): void
    {
        $a = $this->makeProduct(['price' => 3000000, 'name' => 'A']);
        $b = $this->makeProduct(['price' => 1000000, 'name' => 'B']);
        Coupon::create(['code' => 'TECH10', 'discount_type' => 'percent', 'discount_value' => 10, 'min_order_amount' => 3500000, 'max_uses' => 5, 'used_count' => 0, 'is_active' => true]);

        $this->actingAs($this->customer());
        $this->post(route('cart.add'), ['product_id' => $a->id, 'quantity' => 1]);
        $this->post(route('cart.add'), ['product_id' => $b->id, 'quantity' => 1]);
        $this->post(route('cart.coupon.apply'), ['coupon_code' => 'TECH10'])->assertSessionHasNoErrors();

        \App\Models\CartItem::where('product_id', $b->id)->delete(); // tạm tính còn 3.000.000 < 3.500.000

        $this->post(route('checkout.store'), $this->checkoutPayload())->assertSessionHasNoErrors();
        $order = Order::first();
        $this->assertNotNull($order);
        $this->assertEquals(0, (float) $order->discount_amount);
        $this->assertNull($order->coupon_id);
    }

    public function test_admin_can_manage_coupons_and_customer_cannot(): void
    {
        $admin = User::create(['name' => 'Admin', 'email' => 'a@t.vn', 'password' => bcrypt('x'), 'role' => 'admin', 'is_active' => true]);

        $this->actingAs($admin)->get(route('admin.coupons.index'))->assertOk();
        $this->get(route('admin.coupons.create'))->assertOk();
        $this->post(route('admin.coupons.store'), [
            'code' => 'sale5',
            'discount_type' => 'percent',
            'discount_value' => 5,
            'min_order_amount' => 0,
            'max_uses' => 10,
            'is_active' => 1,
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('coupons', ['code' => 'SALE5']);
        $this->get(route('admin.coupons.edit', \App\Models\Coupon::where('code', 'SALE5')->first()))->assertOk();

        $this->post(route('admin.coupons.store'), [
            'code' => 'BAD',
            'discount_type' => 'percent',
            'discount_value' => 150,
            'max_uses' => 10,
        ])->assertSessionHasErrors('discount_value');

        $this->actingAs($this->customer())->get(route('admin.coupons.index'))->assertForbidden();
    }

    public function test_cancelling_order_restores_sold_count_and_coupon_usage(): void
    {
        $p = $this->makeProduct(['price' => 3000000, 'stock_quantity' => 5]);
        Coupon::create(['code' => 'TECH10', 'discount_type' => 'percent', 'discount_value' => 10, 'min_order_amount' => 2000000, 'max_uses' => 5, 'used_count' => 0, 'is_active' => true]);

        $this->actingAs($this->customer());
        $this->post(route('cart.add'), ['product_id' => $p->id, 'quantity' => 2]);
        $this->post(route('cart.coupon.apply'), ['coupon_code' => 'TECH10']);
        $this->post(route('checkout.store'), $this->checkoutPayload());

        $this->assertSame(2, (int) $p->fresh()->sold_count);
        $this->assertSame(1, (int) Coupon::first()->used_count);

        Order::first()->update(['status' => 'cancelled']);

        $this->assertSame(0, (int) $p->fresh()->sold_count);
        $this->assertSame(0, (int) Coupon::first()->used_count);
        $this->assertSame(5, (int) $p->fresh()->stock_quantity);
    }
}
