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
        Schema::table('orders', function (Blueprint $table) {
            // 🔍 PASO 1: Verificar si la foreign key existe antes de eliminarla
            $foreignKeys = $this->getForeignKeys('orders');
            
            // Buscar la foreign key por diferentes nombres posibles
            $foreignKeyName = null;
            foreach ($foreignKeys as $fk) {
                if ($fk->COLUMN_NAME === 'dep_buy_id') {
                    $foreignKeyName = $fk->CONSTRAINT_NAME;
                    break;
                }
            }
            
            // Solo eliminar si existe
            if ($foreignKeyName) {
                $table->dropForeign($foreignKeyName);
            }
        });

        // 🔧 PASO 2: Modificar la columna fuera del Schema::table para evitar conflictos
        DB::statement('ALTER TABLE `orders` MODIFY `dep_buy_id` BIGINT UNSIGNED NULL');

        // 🔗 PASO 3: Recrear la foreign key con onDelete('set null')
        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('dep_buy_id')
                  ->references('id')
                  ->on('dep_buys')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Verificar y eliminar foreign key
            $foreignKeys = $this->getForeignKeys('orders');
            
            foreach ($foreignKeys as $fk) {
                if ($fk->COLUMN_NAME === 'dep_buy_id') {
                    $table->dropForeign($fk->CONSTRAINT_NAME);
                    break;
                }
            }
        });

        // Hacer el campo NOT NULL nuevamente
        DB::statement('ALTER TABLE `orders` MODIFY `dep_buy_id` BIGINT UNSIGNED NOT NULL');

        // Recrear la foreign key original
        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('dep_buy_id')
                  ->references('id')
                  ->on('dep_buys')
                  ->onDelete('cascade');
        });
    }

    /**
     * 🔍 Obtener todas las foreign keys de una tabla
     */
    private function getForeignKeys(string $table): array
    {
        $database = DB::getDatabaseName();
        
        return DB::select("
            SELECT 
                CONSTRAINT_NAME,
                COLUMN_NAME,
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = ?
              AND TABLE_NAME = ?
              AND REFERENCED_TABLE_NAME IS NOT NULL
        ", [$database, $table]);
    }
};