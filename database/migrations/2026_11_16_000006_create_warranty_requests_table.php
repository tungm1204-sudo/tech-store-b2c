<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Đổi trả & Bảo hành thiết bị RMA (Phụ trách: Trung Anh)
 */
return new class extends Migration
{
    public function up()
    {
        Schema::create('warranty_requests', function (Blueprint $table) {
            $table->id();
            $table->string('rma_code', 40)->unique();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('serial_number', 100);
            $table->enum('request_type', ['warranty', 'return'])->default('warranty');
            $table->text('issue_description');
            $table->string('evidence_url', 500)->nullable();
            $table->enum('status', ['submitted', 'inspecting', 'approved', 'rejected', 'completed'])->default('submitted');
            $table->text('resolution_note')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('warranty_requests');
    }
};
