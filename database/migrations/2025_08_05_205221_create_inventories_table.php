<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('product_id')
                  ->constrained('products')
                  ->cascadeOnDelete()
                  ->cascadeOnUpdate();

            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete()
                  ->cascadeOnUpdate();

            $table->foreignId('warehouse_id')
                  ->nullable()
                  ->constrained('warehouses')
                  ->nullOnDelete()
                  ->cascadeOnUpdate();

            // Agregar campos entry_id y output_id si los necesitas
            $table->unsignedBigInteger('entry_id')->nullable();
            $table->unsignedBigInteger('output_id')->nullable();

            $table->string('lot', 50)->nullable();
            $table->decimal('stock', 10, 2)->default(0);
            $table->decimal('min_stock', 10, 2)->default(0);
            $table->string('ubicacion_interna', 255)->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'lot', 'warehouse_id'], 'unique_inventory_per_lot');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};