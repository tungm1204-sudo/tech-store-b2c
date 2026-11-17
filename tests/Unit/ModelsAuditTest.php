<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\Brand;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Models\StockReceipt;
use App\Models\StockReceiptItem;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\WarrantyRequest;

class ModelsAuditTest extends TestCase
{
    public function test_all_16_models_can_query_database()
    {
        $models = [
            Brand::class,
            Cart::class,
            CartItem::class,
            Category::class,
            Coupon::class,
            Order::class,
            OrderItem::class,
            Product::class,
            ProductImage::class,
            ProductVariant::class,
            Review::class,
            StockReceipt::class,
            StockReceiptItem::class,
            User::class,
            UserAddress::class,
            WarrantyRequest::class,
        ];

        foreach ($models as $modelClass) {
            $count = $modelClass::count();
            $this->assertIsInt($count, "Failed querying count on {$modelClass}");
        }
    }

    public function test_relationships_integrity()
    {
        $product = Product::with(['category', 'brand', 'images', 'variants', 'reviews'])->first();
        if ($product) {
            $this->assertNotNull($product->category);
            $this->assertNotNull($product->brand);
        }

        $order = Order::with(['user', 'items.product', 'warrantyRequests'])->first();
        if ($order) {
            $this->assertNotNull($order->user);
            $this->assertNotEmpty($order->items);
        }

        $receipt = StockReceipt::with(['creator', 'items.product'])->first();
        if ($receipt) {
            $this->assertNotNull($receipt->creator);
            $this->assertNotEmpty($receipt->items);
        }
    }
}
