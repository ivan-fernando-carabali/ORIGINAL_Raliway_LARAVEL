<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Verificar si ya existen antes de agregarlos
            if (!Schema::hasColumn('orders', 'product_id')) {
                $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            }
            if (!Schema::hasColumn('orders', 'inventory_id')) {
                $table->foreignId('inventory_id')->nullable()->constrained()->nullOnDelete();
            }
            if (!Schema::hasColumn('orders', 'supplier_id')) {
                $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            }
            if (!Schema::hasColumn('orders', 'alert_id')) {
                $table->foreignId('alert_id')->nullable()->constrained()->nullOnDelete();
            }
            if (!Schema::hasColumn('orders', 'quantity')) {
                $table->unsignedInteger('quantity')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Solo eliminar si existen
            if (Schema::hasColumn('orders', 'product_id')) {
                $table->dropForeign(['product_id']);
                $table->dropColumn('product_id');
            }
            if (Schema::hasColumn('orders', 'inventory_id')) {
                $table->dropForeign(['inventory_id']);
                $table->dropColumn('inventory_id');
            }
            if (Schema::hasColumn('orders', 'supplier_id')) {
                $table->dropForeign(['supplier_id']);
                $table->dropColumn('supplier_id');
            }
            if (Schema::hasColumn('orders', 'alert_id')) {
                $table->dropForeign(['alert_id']);
                $table->dropColumn('alert_id');
            }
            if (Schema::hasColumn('orders', 'quantity')) {
                $table->dropColumn('quantity');
            }
        });
    }
};
