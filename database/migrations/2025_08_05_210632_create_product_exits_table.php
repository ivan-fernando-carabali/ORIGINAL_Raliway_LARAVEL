<?php
// database/migrations/2025_09_21_000005_create_outputs_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('product_exits', function (Blueprint $table) {
    $table->id(); // ID salida
    $table->foreignId('product_id')->constrained('products'); // Producto
    $table->integer('quantity'); // Cantidad
    $table->string('unit')->nullable(); // Unidad de medida
    $table->string('lot')->nullable(); // Lote
    $table->foreignId('user_id')->constrained('users'); // Usuario
    $table->foreignId('inventory_id')->constrained('inventories'); // Inventario
    $table->timestamps();
});

    }
    public function down(): void {
        Schema::dropIfExists('outputs');
    }
};
