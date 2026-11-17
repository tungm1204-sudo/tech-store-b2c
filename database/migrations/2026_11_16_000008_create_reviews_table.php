<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Mô-đun Đánh giá & Bình luận sản phẩm (Phụ trách chính: Nguyễn Minh Tự - 20232059)
 */
return new class extends Migration
{
    public function up()
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
            $table->unsignedTinyInteger('rating'); // 1..5 sao
            $table->string('title', 180)->nullable();
            $table->string('pros', 255)->nullable(); // Ưu điểm nổi bật
            $table->string('cons', 255)->nullable(); // Nhược điểm cần lưu ý
            $table->text('comment');
            $table->boolean('is_verified_purchase')->default(false);
            $table->boolean('is_approved')->default(true);
            $table->text('admin_reply')->nullable();
            $table->timestamp('replied_at')->nullable();
            $table->foreignId('replied_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['product_id', 'is_approved', 'rating']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('reviews');
    }
};
