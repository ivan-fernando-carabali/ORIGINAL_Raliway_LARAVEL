<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
   public function up(): void {
    Schema::create('entries', function (Blueprint $table) {
        $table->id();
        $table->foreignId('product_id')->constrained('products');
        $table->integer('quantity');
        $table->string('unit')->nullable();
        $table->string('lot')->nullable();
        $table->foreignId('supplier_id')->constrained('suppliers');
        $table->foreignId('location_id')->constrained('locations');
        $table->foreignId('warehouse_id')->constrained('warehouses'); // ✅ corregido
        $table->integer('stock')->default(0);
        $table->integer('min_stock')->default(0);
        $table->foreignId('user_id')->constrained('users');
        $table->timestamps();
    });
}

    public function down(): void {
        Schema::dropIfExists('entries');
    }
};
