<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->decimal('quantity', 10, 2);
            $table->string('unit', 20)->nullable();
            $table->string('lot', 50)->nullable();
            $table->date('expiration_date')->nullable(); // ✅ AGREGADO
            $table->foreignId('supplier_id')->constrained('suppliers')->onDelete('cascade');
            $table->foreignId('warehouse_id')->constrained('warehouses')->onDelete('cascade');
            $table->string('ubicacion_interna')->nullable(); // ✅ CAMBIADO DE location_id
            $table->decimal('stock', 10, 2)->default(0);
            $table->decimal('min_stock', 10, 2)->default(0);
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            
            // Índices para optimizar búsquedas
            $table->index(['product_id', 'lot']);
            $table->index('warehouse_id');
        });
    }

    public function down(): void {
        Schema::dropIfExists('entries');
    }
};