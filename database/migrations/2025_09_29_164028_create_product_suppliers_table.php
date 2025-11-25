<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_supplier', function (Blueprint $table) {
            // 🔹 Campos pivot
            $table->decimal('unit_cost', 10, 2);
            $table->string('supplier_reference', 50)->nullable();
            $table->string('batch', 100)->nullable();

            // 🔹 Relaciones
            $table->foreignId('supplier_id')
                ->constrained('suppliers')
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            // 🔹 Llave compuesta
            $table->primary(['supplier_id', 'product_id']);

            // 🔹 Fechas de creación/actualización
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_supplier');
    }
};
