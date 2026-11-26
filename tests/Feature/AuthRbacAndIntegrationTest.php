<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Kiểm thử tích hợp toàn hệ thống sau khi ghép cả 5 phân hệ vào nhánh main:
 *  - Điền: Auth, RBAC, Quên mật khẩu (SHA-256 token 60 phút), Hồ sơ & Sổ địa chỉ, Admin Users
 *  - Tuyến: Lọc danh mục theo slug + ID, Form danh mục Admin, Trang chi tiết tích hợp Cart + Review
 *  - Phúc + Trung Anh: Kiểm chứng chặn lỗi Hoàn kho gấp đôi (Double Restock) khi hủy đơn
 */
class AuthRbacAndIntegrationTest extends TestCase
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

    public function test_register_always_creates_customer_and_login_logout_works(): void
    {
        $this->post('/register', [
            'name'                  => 'Nguyễn Văn Điền',
            'email'                 => 'dien.test@example.com',
            'phone'                 => '0912345678',
            'password'              => 'secret1234',
            'password_confirmation' => 'secret1234',
            'role'                  => 'admin', // thử leo thang đặc quyền -> phải bị bỏ qua
        ])->assertRedirect(route('login'));

        $user = User::where('email', 'dien.test@example.com')->firstOrFail();
        $this->assertSame('customer', $user->role);
        $this->assertTrue($user->is_active);

        $this->post('/login', [
            'email'    => 'dien.test@example.com',
            'password' => 'secret1234',
        ])->assertRedirect(route('home'));
        $this->assertAuthenticatedAs($user);

        $this->post('/logout')->assertRedirect(route('home'));
        $this->assertGuest();
    }

    public function test_locked_user_cannot_login(): void
    {
        User::create([
            'name'      => 'Locked User',
            'email'     => 'locked@example.com',
            'password'  => Hash::make('secret1234'),
            'role'      => 'customer',
            'is_active' => false,
        ]);

        $this->post('/login', [
            'email'    => 'locked@example.com',
            'password' => 'secret1234',
        ])->assertSessionHas('error');

        $this->assertGuest();
    }

    public function test_password_reset_token_is_hashed_single_use_and_expires(): void
    {
        $user = User::create([
            'name'      => 'Reset User',
            'email'     => 'reset@example.com',
            'password'  => Hash::make('oldpass123'),
            'role'      => 'customer',
            'is_active' => true,
        ]);

        $rawToken = bin2hex(random_bytes(32));
        DB::table('password_resets')->insert([
            'email'      => $user->email,
            'token'      => hash('sha256', $rawToken),
            'created_at' => now(),
        ]);

        $this->get(route('password.reset', ['token' => $rawToken, 'email' => $user->email]))
            ->assertOk()
            ->assertSee('Lưu mật khẩu mới');

        $this->post(route('password.update'), [
            'token'                 => $rawToken,
            'email'                 => $user->email,
            'password'              => 'newpass1234',
            'password_confirmation' => 'newpass1234',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('newpass1234', $user->fresh()->password));
        $this->assertDatabaseMissing('password_resets', ['email' => $user->email]);

        // Dùng lại token cũ phải thất bại
        $this->post(route('password.update'), [
            'token'                 => $rawToken,
            'email'                 => $user->email,
            'password'              => 'another1234',
            'password_confirmation' => 'another1234',
        ])->assertSessionHas('error');
    }

    public function test_profile_address_book_crud_default_promotion_and_idor_protection(): void
    {
        $u1 = User::create(['name' => 'U1', 'email' => 'u1@t.vn', 'password' => Hash::make('secret1234'), 'role' => 'customer', 'is_active' => true]);
        $u2 = User::create(['name' => 'U2', 'email' => 'u2@t.vn', 'password' => Hash::make('secret1234'), 'role' => 'customer', 'is_active' => true]);

        $this->actingAs($u1);
        $this->post(route('profile.addresses.store'), [
            'recipient_name'  => 'A',
            'recipient_phone' => '0901111222',
            'province'        => 'Hà Nội',
            'district'        => 'Cầu Giấy',
            'ward'            => 'Dịch Vọng',
            'street_address'  => '12 Xuân Thủy',
        ])->assertRedirect(route('profile.index'));

        $addr1 = UserAddress::firstOrFail();
        $this->assertTrue($addr1->is_default); // địa chỉ đầu tự mặc định

        $this->post(route('profile.addresses.store'), [
            'recipient_name'  => 'B',
            'recipient_phone' => '0903333444',
            'province'        => 'TP.HCM',
            'district'        => 'Quận 1',
            'street_address'  => '99 Lê Lợi',
        ]);
        $addr2 = UserAddress::whereKeyNot($addr1->id)->firstOrFail();
        $this->assertFalse($addr2->is_default);

        // Chống IDOR: U2 không thể xóa hay sửa địa chỉ của U1
        $this->actingAs($u2)->delete(route('profile.addresses.destroy', $addr1))->assertNotFound();

        // Xóa địa chỉ mặc định -> tự đôn addr2 lên mặc định
        $this->actingAs($u1)->delete(route('profile.addresses.destroy', $addr1))->assertRedirect(route('profile.index'));
        $this->assertTrue($addr2->fresh()->is_default);
    }

    public function test_admin_users_rbac_and_self_protection(): void
    {
        $admin = User::create(['name' => 'Admin', 'email' => 'adm@t.vn', 'password' => Hash::make('secret1234'), 'role' => 'admin', 'is_active' => true]);
        $staff = User::create(['name' => 'Staff', 'email' => 'stf@t.vn', 'password' => Hash::make('secret1234'), 'role' => 'staff', 'is_active' => true]);
        $cust = User::create(['name' => 'Cust', 'email' => 'cus@t.vn', 'password' => Hash::make('secret1234'), 'role' => 'customer', 'is_active' => true]);

        $this->actingAs($cust)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('admin.users.index'))->assertForbidden();

        $this->actingAs($admin)->get(route('admin.users.index'))->assertOk()->assertSee('adm@t.vn');

        // Không được tự khóa chính mình
        $this->patch(route('admin.users.toggle_status', $admin))->assertSessionHas('error');
        $this->assertTrue($admin->fresh()->is_active);

        // Đổi role & khóa tài khoản khác thành công
        $this->patch(route('admin.users.role', $cust), ['role' => 'staff'])->assertSessionHas('success');
        $this->assertSame('staff', $cust->fresh()->role);

        $this->patch(route('admin.users.toggle_status', $cust))->assertSessionHas('success');
        $this->assertFalse($cust->fresh()->is_active);
    }

    public function test_catalog_slug_filter_category_form_and_product_detail_integration(): void
    {
        $admin = User::create(['name' => 'Admin', 'email' => 'adm2@t.vn', 'password' => Hash::make('secret1234'), 'role' => 'admin', 'is_active' => true]);
        $cat = Category::create(['name' => 'Laptop', 'slug' => 'laptop', 'icon' => 'bi-laptop']);
        $brand = Brand::create(['name' => 'Apple', 'slug' => 'apple', 'country' => 'Mỹ']);
        $p = Product::create([
            'category_id'    => $cat->id,
            'brand_id'       => $brand->id,
            'sku'            => 'MBP-14',
            'name'           => 'MacBook Pro 14 M3',
            'slug'           => 'macbook-pro-14-m3',
            'price'          => 45000000,
            'stock_quantity' => 5,
            'is_active'      => true,
        ]);
        ProductVariant::create(['product_id' => $p->id, 'variant_name' => '18GB/512GB', 'sku_suffix' => '512', 'price_adjustment' => 0, 'stock_quantity' => 5]);

        // Lỗi Tuyến đã fix: lọc ?category=laptop (slug từ menu header) phải trả về đúng sản phẩm
        $this->get('/products?category=laptop')->assertOk()->assertSee('MacBook Pro 14 M3');

        // Trang chi tiết sản phẩm phải có nút Thêm vào giỏ (Phúc) và khối Đánh giá (Tự)
        $this->get('/products/macbook-pro-14-m3')
            ->assertOk()
            ->assertSee('Thêm vào giỏ hàng')
            ->assertSee('18GB/512GB')
            ->assertSee('Đánh giá & Nhận xét', false);

        // Form tạo danh mục đã fix không còn nhầm "Tên thương hiệu"
        $this->actingAs($admin)
            ->get(route('admin.categories.create'))
            ->assertOk()
            ->assertSee('Tên danh mục')
            ->assertDontSee('Tên thương hiệu');
    }

    public function test_checkout_plus_trung_anh_cancel_restocks_exactly_once_no_double_restock(): void
    {
        $cat = Category::create(['name' => 'Phone', 'slug' => 'phone']);
        $brand = Brand::create(['name' => 'Apple', 'slug' => 'apple-2']);
        $p = Product::create([
            'category_id'    => $cat->id,
            'brand_id'       => $brand->id,
            'sku'            => 'IP16-PM',
            'name'           => 'iPhone 16 Pro Max',
            'slug'           => 'iphone-16-pm-test',
            'price'          => 30000000,
            'stock_quantity' => 10,
            'sold_count'     => 0,
            'is_active'      => true,
        ]);
        $v = ProductVariant::create(['product_id' => $p->id, 'variant_name' => '256GB Titan', 'sku_suffix' => '256', 'price_adjustment' => 0, 'stock_quantity' => 6]);
        $coupon = Coupon::create(['code' => 'TEST10', 'discount_type' => 'percent', 'discount_value' => 10, 'min_order_amount' => 1000000, 'max_uses' => 5, 'used_count' => 0, 'is_active' => true]);
        $customer = User::create(['name' => 'Khach', 'email' => 'khach@t.vn', 'password' => Hash::make('secret1234'), 'role' => 'customer', 'is_active' => true]);

        // 1. Khách mua 2 máy qua Checkout của Phúc (tồn 10 -> 8, variant 6 -> 4, sold 0 -> 2, coupon 0 -> 1)
        $this->actingAs($customer);
        $this->post(route('cart.add'), ['product_id' => $p->id, 'variant_id' => $v->id, 'quantity' => 2]);
        $this->post(route('cart.coupon.apply'), ['coupon_code' => 'TEST10']);
        $this->post(route('checkout.store'), [
            'customer_name'    => 'Khach',
            'customer_phone'   => '0912345678',
            'shipping_address' => '123 Cau Giay, Ha Noi',
            'payment_method'   => 'cod',
        ])->assertSessionHasNoErrors();

        $order = Order::firstOrFail();
        $this->assertSame(8, (int) $p->fresh()->stock_quantity);
        $this->assertSame(4, (int) $v->fresh()->stock_quantity);
        $this->assertSame(2, (int) $p->fresh()->sold_count);
        $this->assertSame(1, (int) $coupon->fresh()->used_count);

        // 2. Khách hủy đơn qua OrderController của Trung Anh -> chỉ hoàn ĐÚNG 1 LẦN (10, 6, 0, 0), KHÔNG được phình thành 12!
        $this->post(route('orders.cancel', $order), ['reason' => 'Đổi ý'])
            ->assertRedirect();

        $this->assertSame('cancelled', $order->fresh()->status);
        $this->assertSame(10, (int) $p->fresh()->stock_quantity);
        $this->assertSame(6, (int) $v->fresh()->stock_quantity);
        $this->assertSame(0, (int) $p->fresh()->sold_count);
        $this->assertSame(0, (int) $coupon->fresh()->used_count);
    }
}
