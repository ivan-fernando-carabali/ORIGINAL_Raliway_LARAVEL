<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {Schema::create('exit_details', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('product_id');       // FK to products
    $table->unsignedBigInteger('product_exit_id');  // FK to product_exits
    $table->integer('quantity');
    $table->string('destination', 50);

    $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
    $table->foreign('product_exit_id')->references('id')->on('product_exits')->onDelete('cascade');
});

    }

    public function down(): void
    {
        Schema::dropIfExists('exit_details');
    }
};
