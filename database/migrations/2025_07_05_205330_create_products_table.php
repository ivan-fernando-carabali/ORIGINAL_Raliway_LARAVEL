<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta la migración.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            // 🔗 Relación con categoría
            $table->foreignId('category_id')
                ->nullable()
                ->constrained('categories')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            // 🏷️ Información básica del producto
            $table->string('name', 100);
            $table->string('reference', 50)->nullable();
            $table->string('unit_measurement', 20)->nullable();
            $table->string('batch', 50)->nullable();
            $table->date('expiration_date')->nullable();

            // 🖼️ Imagen del producto
            $table->string('image', 255)->nullable();

            // 🕒 Timestamps (created_at, updated_at)
            $table->timestamps();
        });
    }

    /**
     * Revertir la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
