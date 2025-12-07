<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Usar DB::statement para modificar la columna directamente en MySQL
        // Esto asegura que el campo date tenga un valor por defecto
        DB::statement("ALTER TABLE `orders` MODIFY COLUMN `date` DATE NOT NULL DEFAULT (CURRENT_DATE)");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Revertir el cambio, removiendo el valor por defecto
            $table->date('date')->nullable(false)->change();
        });
    }
};
