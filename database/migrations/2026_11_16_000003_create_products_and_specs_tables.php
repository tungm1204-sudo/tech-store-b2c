<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Sản phẩm công nghệ, Thư viện ảnh & Biến thể cấu hình (Phụ trách: Tuyến)
 */
return new class extends Migration
{
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->foreignId('brand_id')->constrained('brands')->restrictOnDelete();
            $table->string('sku', 80)->unique();
            $table->string('name', 255);
            $table->string('slug', 255)->unique();
            $table->string('short_description', 500)->nullable();
            $table->longText('description')->nullable();
            $table->decimal('price', 15, 2);
            $table->decimal('sale_price', 15, 2)->nullable();
            $table->unsignedInteger('stock_quantity')->default(0);
            $table->unsignedInteger('low_stock_threshold')->default(5);
            $table->unsignedInteger('sold_count')->default(0);
            $table->unsignedInteger('warranty_months')->default(12);
            // Các thông số đặc thù của thiết bị công nghệ phục vụ lọc & so sánh
            $table->string('cpu', 120)->nullable();
            $table->string('ram', 60)->nullable();
            $table->string('rom', 60)->nullable();
            $table->string('gpu', 120)->nullable();
            $table->string('screen', 120)->nullable();
            $table->string('battery', 80)->nullable();
            $table->string('usage_type', 100)->default('all'); // gaming, van-phong, do-hoa, all
            $table->json('specs_json')->nullable();
            $table->string('thumbnail', 500)->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['category_id', 'brand_id', 'is_active']);
            $table->index(['usage_type', 'ram']);
        });

        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('image_path', 500);
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });

        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('variant_name', 120); // VD: 16GB / 512GB / Silver
            $table->string('sku_suffix', 40);
            $table->decimal('price_adjustment', 15, 2)->default(0);
            $table->unsignedInteger('stock_quantity')->default(0);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('product_images');
        Schema::dropIfExists('products');
    }
};
