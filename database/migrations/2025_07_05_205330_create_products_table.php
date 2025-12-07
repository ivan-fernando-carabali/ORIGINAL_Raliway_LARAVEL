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

            // 🔗 Relación con categorías (permite NULL)
            $table->foreignId('category_id')
                ->nullable()
                ->constrained('categories')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            // 🏷️ Información básica del producto
            $table->string('name', 150); // más flexible
            $table->string('reference', 100)->nullable();
            $table->string('unit_measurement', 50)->nullable();
            $table->string('batch', 100)->nullable();

            // ❗ Corrección: permitir NULL evita errores en MySQL
            $table->date('expiration_date')->nullable();

            // 🖼️ Imagen principal
            $table->string('image')->nullable();

            // 🕒 created_at / updated_at
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
