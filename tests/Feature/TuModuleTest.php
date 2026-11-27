<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Review;

class TuModuleTest extends TestCase
{
    public function test_home_page()
    {
        $res = $this->get('/');
        $res->assertStatus(200);
    }

    public function test_ai_advisor()
    {
        $res = $this->get('/ai-advisor');
        $res->assertStatus(200);
    }

    public function test_review_page()
    {
        $p = Product::first();
        if ($p) {
            $res = $this->get('/products/' . $p->slug . '/reviews');
            $res->assertStatus(200);
        }
    }

    public function test_admin_dashboard()
    {
        $admin = User::where('role', 'admin')->first();
        $res = $this->actingAs($admin)->get('/admin/dashboard');
        $res->assertStatus(200);
    }

    public function test_export_revenue_csv()
    {
        $admin = User::where('role', 'admin')->first();
        $res = $this->actingAs($admin)->get('/admin/reports/export?type=revenue');
        $res->assertStatus(200);
        $res->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_api_products()
    {
        $res = $this->get('/api/v1/products');
        $res->assertStatus(200);
        $res->assertJsonStructure(['status', 'data' => ['data', 'total']]);
    }

    public function test_customer_can_post_review()
    {
        $customer = User::where('role', 'customer')->first();
        $product = Product::first();
        $res = $this->actingAs($customer)->post('/products/' . $product->id . '/reviews', [
            'rating' => 5,
            'title' => 'Sản phẩm quá đỉnh',
            'pros' => 'Màn hình đẹp, pin trâu',
            'cons' => 'Hơi nặng',
            'comment' => 'Dùng thực tế 2 tuần rất mượt mà, đáng đồng tiền bát gạo.',
        ]);
        $res->assertStatus(302);
        $this->assertDatabaseHas('reviews', [
            'user_id' => $customer->id,
            'product_id' => $product->id,
            'rating' => 5,
        ]);
    }
}
