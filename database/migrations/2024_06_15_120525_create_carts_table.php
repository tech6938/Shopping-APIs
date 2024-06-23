<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            $table->string('order_id');
            $table->string('product_id');
            $table->string('order_date')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('payment_date')->nullable();
            $table->string('retailer_id');
            $table->string('cashier_id');
            $table->string('quantity');
            $table->string('place_status')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
