<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Dấu thời gian giúp tránh hoàn kho nhiều lần khi đơn bị hủy.
 * Bổ sung cột vào orders hiện có, không thay đổi cấu trúc 16 bảng nghiệp vụ.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->timestamp('stock_deducted_at')->nullable()->after('status');
            $table->timestamp('stock_restored_at')->nullable()->after('stock_deducted_at');
        });
    }

    public function down()
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['stock_deducted_at', 'stock_restored_at']);
        });
    }
};