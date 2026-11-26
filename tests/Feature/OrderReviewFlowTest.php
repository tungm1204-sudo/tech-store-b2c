<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderReviewFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function refreshApplication()
    {
        parent::refreshApplication();
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
    }

    private function makeProduct(int $stock = 5): Product
    {
        $cat = Category::firstOrCreate(['slug' => 'laptop'], ['name' => 'Laptop']);
        $brand = Brand::firstOrCreate(['slug' => 'dell'], ['name' => 'Dell']);

        return Product::create([
            'category_id' => $cat->id,
            'brand_id' => $brand->id,
            'sku' => 'SKU-' . uniqid(),
            'name' => 'Laptop Test',
            'slug' => 'laptop-test-' . uniqid(),
            'price' => 10000000,
            'stock_quantity' => $stock,
        ]);
    }

    private function user(string $role): User
    {
        return User::create([
            'name' => ucfirst($role) . ' Test',
            'email' => $role . uniqid() . '@test.vn',
            'password' => bcrypt('password'),
            'phone' => '0900000000',
            'address' => 'Ha Noi',
            'role' => $role,
            'is_active' => true,
        ]);
    }

    private function placeOrder(User $customer, Product $product, int $qty, string $method): Order
    {
        $this->actingAs($customer)
            ->post(route('cart.add'), ['product_id' => $product->id, 'quantity' => $qty])
            ->assertSessionHasNoErrors();

        $this->actingAs($customer)->post(route('checkout.store'), [
            'customer_name' => 'Nguyen Van A',
            'customer_phone' => '0912345678',
            'shipping_address' => '1 Tran Duy Hung, Ha Noi',
            'payment_method' => $method,
        ])->assertSessionHasNoErrors();

        return Order::where('user_id', $customer->id)->latest('id')->firstOrFail();
    }

    public function test_new_cod_order_reaches_staff_and_can_be_accepted(): void
    {
        $product = $this->makeProduct(5);
        $order = $this->placeOrder($this->user('customer'), $product, 2, 'cod');
        $staff = $this->user('staff');

        $this->assertSame('pending', $order->status);
        $this->assertSame(3, $product->fresh()->stock_quantity); // Checkout đã trừ kho

        $this->actingAs($staff)->get(route('admin.orders.index'))->assertOk()->assertSee($order->order_code);
        $this->actingAs($staff)->get(route('admin.orders.show', $order))->assertOk()->assertSee('Kiểm tra đơn mới');

        $this->actingAs($staff)
            ->post(route('admin.orders.transition', $order), ['to' => 'confirmed'])
            ->assertSessionHas('success');

        $this->assertSame('confirmed', $order->fresh()->status);
        $this->assertSame(3, $product->fresh()->stock_quantity); // duyệt không đổi kho
    }

    public function test_qr_order_cannot_be_accepted_until_payment_is_confirmed(): void
    {
        $product = $this->makeProduct();
        $order = $this->placeOrder($this->user('customer'), $product, 1, 'qr_transfer');
        $staff = $this->user('staff');

        $this->actingAs($staff)
            ->post(route('admin.orders.transition', $order), ['to' => 'confirmed'])
            ->assertSessionHas('error');
        $this->assertSame('pending', $order->fresh()->status);

        $this->actingAs($staff)->post(route('admin.orders.mark_paid', $order))->assertSessionHas('success');
        $this->assertSame('paid', $order->fresh()->payment_status);

        $this->actingAs($staff)
            ->post(route('admin.orders.transition', $order), ['to' => 'confirmed'])
            ->assertSessionHas('success');
        $this->assertSame('confirmed', $order->fresh()->status);
    }

    public function test_order_with_mismatched_total_is_blocked(): void
    {
        $product = $this->makeProduct();
        $order = $this->placeOrder($this->user('customer'), $product, 1, 'cod');
        $order->update(['total_amount' => 1000]); // dữ liệu bị sai lệch

        $this->actingAs($this->user('staff'))
            ->post(route('admin.orders.transition', $order), ['to' => 'confirmed'])
            ->assertSessionHas('error');
        $this->assertSame('pending', $order->fresh()->status);
    }

    public function test_declining_requires_reason_and_restores_stock_once(): void
    {
        $product = $this->makeProduct(5);
        $order = $this->placeOrder($this->user('customer'), $product, 2, 'cod');
        $staff = $this->user('staff');

        $this->actingAs($staff)
            ->post(route('admin.orders.transition', $order), ['to' => 'cancelled'])
            ->assertSessionHasErrors('reason');
        $this->assertSame('pending', $order->fresh()->status);
        $this->assertSame(3, $product->fresh()->stock_quantity);

        $this->actingAs($staff)
            ->post(route('admin.orders.transition', $order), ['to' => 'cancelled', 'reason' => 'Sản phẩm tạm hết hàng'])
            ->assertSessionHas('success');

        $order->refresh();
        $this->assertSame('cancelled', $order->status);
        $this->assertSame('Sản phẩm tạm hết hàng', $order->cancelled_reason);
        $this->assertSame(5, $product->fresh()->stock_quantity);

        $this->actingAs($staff)
            ->post(route('admin.orders.transition', $order), ['to' => 'cancelled', 'reason' => 'Thử hủy lần hai'])
            ->assertSessionHas('error');
        $this->assertSame(5, $product->fresh()->stock_quantity);
    }

    public function test_customer_sees_decline_reason_on_their_order(): void
    {
        $customer = $this->user('customer');
        $order = $this->placeOrder($customer, $this->makeProduct(), 1, 'cod');

        $this->actingAs($this->user('staff'))
            ->post(route('admin.orders.transition', $order), ['to' => 'cancelled', 'reason' => 'Không liên hệ được khách hàng']);

        $this->actingAs($customer)->get(route('orders.show', $order))
            ->assertOk()
            ->assertSee('Không liên hệ được khách hàng');
    }

    public function test_customer_cannot_use_staff_order_screens(): void
    {
        $customer = $this->user('customer');
        $order = $this->placeOrder($customer, $this->makeProduct(), 1, 'cod');

        $this->actingAs($customer)->get(route('admin.orders.index'))->assertForbidden();
        $this->actingAs($customer)
            ->post(route('admin.orders.transition', $order), ['to' => 'confirmed'])
            ->assertForbidden();
    }
}
