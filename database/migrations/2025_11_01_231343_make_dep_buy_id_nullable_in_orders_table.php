<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {

            /* -----------------------------------------
                1) DEP_BUY_ID → Hacerlo nullable si existe
            ----------------------------------------- */
            if (Schema::hasColumn('orders', 'dep_buy_id')) {
                $table->unsignedBigInteger('dep_buy_id')->nullable()->change();
            }

            /* -----------------------------------------
                2) Agregar columnas SOLO si no existen
            ----------------------------------------- */

            if (!Schema::hasColumn('orders', 'supplier_id')) {
                $table->foreignId('supplier_id')
                    ->nullable()
                    ->after('dep_buy_id')
                    ->constrained('suppliers')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('orders', 'product_id')) {
                $table->foreignId('product_id')
                    ->nullable()
                    ->after('supplier_id')
                    ->constrained('products')
                    ->cascadeOnDelete();
            }

            if (!Schema::hasColumn('orders', 'inventory_id')) {
                $table->foreignId('inventory_id')
                    ->nullable()
                    ->after('product_id')
                    ->constrained('inventories')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('orders', 'alert_id')) {
                $table->foreignId('alert_id')
                    ->nullable()
                    ->after('inventory_id')
                    ->constrained('alerts')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('orders', 'quantity')) {
                $table->integer('quantity')->after('alert_id');
            }

            if (!Schema::hasColumn('orders', 'supplier_email')) {
                $table->string('supplier_email')->nullable()->after('quantity');
            }

            if (!Schema::hasColumn('orders', 'notes')) {
                $table->text('notes')->nullable()->after('supplier_email');
            }

            if (!Schema::hasColumn('orders', 'sent_at')) {
                $table->timestamp('sent_at')->nullable()->after('status');
            }

            if (!Schema::hasColumn('orders', 'received_at')) {
                $table->timestamp('received_at')->nullable()->after('sent_at');
            }

            /* -----------------------------------------
                3) Cambiar state → status SOLO si existe
            ----------------------------------------- */
            if (Schema::hasColumn('orders', 'state') && !Schema::hasColumn('orders', 'status')) {
                $table->renameColumn('state', 'status');
            }
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {

            if (Schema::hasColumn('orders', 'supplier_id')) {
                $table->dropForeign(['supplier_id']);
                $table->dropColumn('supplier_id');
            }

            if (Schema::hasColumn('orders', 'product_id')) {
                $table->dropForeign(['product_id']);
                $table->dropColumn('product_id');
            }

            if (Schema::hasColumn('orders', 'inventory_id')) {
                $table->dropForeign(['inventory_id']);
                $table->dropColumn('inventory_id');
            }

            if (Schema::hasColumn('orders', 'alert_id')) {
                $table->dropForeign(['alert_id']);
                $table->dropColumn('alert_id');
            }

            foreach (['quantity', 'supplier_email', 'notes', 'sent_at', 'received_at'] as $col) {
                if (Schema::hasColumn('orders', $col)) {
                    $table->dropColumn($col);
                }
            }

            if (Schema::hasColumn('orders', 'status') && !Schema::hasColumn('orders', 'state')) {
                $table->renameColumn('status', 'state');
            }

            if (Schema::hasColumn('orders', 'dep_buy_id')) {
                $table->unsignedBigInteger('dep_buy_id')->nullable(false)->change();
            }
        });
    }
};
