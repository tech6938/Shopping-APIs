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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('category_id');
            $table->string('title');
            $table->integer('price');
            $table->string('units');
            $table->string('color')->nullable()->default('N/A');
            $table->string('storage')->nullable()->default('N/A');
            $table->string('screenSize')->nullable()->default('N/A');
            $table->string('screenResolution')->nullable()->default('N/A');
            $table->string('camera')->nullable()->default('N/A');
            $table->string('cameraLens')->nullable()->default('N/A');
            $table->string('Ram')->nullable()->default('N/A');
            $table->string('processor')->nullable()->default('N/A');
            $table->string('battery')->nullable()->default('N/A');
            $table->string('charging')->nullable()->default('N/A');
            $table->string('image');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
