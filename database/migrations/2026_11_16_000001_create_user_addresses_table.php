<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: Sổ địa chỉ cá nhân (Phụ trách: Điền)
 */
return new class extends Migration
{
    public function up()
    {
        Schema::create('user_addresses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('recipient_name', 120);
            $table->string('recipient_phone', 25);
            $table->string('province', 100);
            $table->string('district', 100);
            $table->string('ward', 100)->nullable();
            $table->string('street_address', 255);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_addresses');
    }
};
