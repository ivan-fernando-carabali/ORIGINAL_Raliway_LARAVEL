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
            // Verificar si ya existe antes de agregarlo
            if (!Schema::hasColumn('orders', 'supplier_email')) {
                $table->string('supplier_email')->nullable()->after('supplier_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Solo eliminar si existe
            if (Schema::hasColumn('orders', 'supplier_email')) {
                $table->dropColumn('supplier_email');
            }
        });
    }
};
