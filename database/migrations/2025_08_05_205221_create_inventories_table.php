
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
        Schema::create('inventories', function (Blueprint $table) {
            $table->id();

            // 🔗 Relaciones
            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

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


            // 🧾 Lote
            $table->string('lot')->nullable();

            // 📦 Datos del inventario
            $table->integer('stock')->default(0);
            $table->integer('min_stock')->default(0);

            // 🏷️ Ubicación interna dentro del almacén
            $table->string('ubicacion_interna')->nullable();

            $table->timestamps();

            // 🔍 Clave única: producto + lote + almacén
            $table->unique(['product_id', 'lot', 'warehouse_id'], 'unique_inventory_per_lot');

            //
        });
    }

    /**
     * Reviertes la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventories');
    }
};
