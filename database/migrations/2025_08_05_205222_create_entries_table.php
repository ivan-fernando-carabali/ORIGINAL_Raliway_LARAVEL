<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('entries', function (Blueprint $table) {
            $table->id(); // ID de la entrada
            $table->foreignId('product_id')->constrained('products'); // Producto
            $table->integer('quantity'); // Cantidad ingresada
            $table->string('unit')->nullable(); // Unidad de medida
            $table->string('lot')->nullable(); // Lote
            $table->foreignId('supplier_id')->constrained('suppliers'); // Proveedor
            $table->string('ubicacion_interna'); // Ubicación interna
            $table->integer('stock')->default(0); // ✅ Stock con valor por defecto
            $table->integer('min_stock')->default(0); // ✅ Stock mínimo con valor por defecto
            $table->foreignId('user_id')->constrained('users'); // Usuario que crea la entrada
            $table->timestamps(); // created_at y updated_at
        });
    }

    public function down(): void {
        Schema::dropIfExists('entries');
    }
};
