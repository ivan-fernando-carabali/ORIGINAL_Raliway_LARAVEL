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
        try {
            // Intentar modificar la columna con valor por defecto
            // Usar sintaxis compatible con diferentes versiones de MySQL
            DB::statement("ALTER TABLE `orders` MODIFY COLUMN `date` DATE NOT NULL DEFAULT (CURRENT_DATE)");
        } catch (\Exception $e) {
            // Si falla, intentar con sintaxis alternativa (sin paréntesis)
            try {
                DB::statement("ALTER TABLE `orders` MODIFY COLUMN `date` DATE NOT NULL DEFAULT CURRENT_DATE");
            } catch (\Exception $e2) {
                // Si ambas fallan, al menos hacer el campo nullable temporalmente
                // El código del controlador se encargará de proporcionar siempre un valor
                DB::statement("ALTER TABLE `orders` MODIFY COLUMN `date` DATE NULL");
            }
        }
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
