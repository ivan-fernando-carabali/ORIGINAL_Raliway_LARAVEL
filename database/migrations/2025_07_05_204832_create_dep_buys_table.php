<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Verificar y eliminar constraints
        $result = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.TABLE_CONSTRAINTS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'orders' 
            AND CONSTRAINT_TYPE = 'FOREIGN KEY'
        ");

        foreach ($result as $constraint) {
            if (stripos($constraint->CONSTRAINT_NAME, 'dep_buy') !== false) {
                try {
                    DB::statement("ALTER TABLE orders DROP FOREIGN KEY `{$constraint->CONSTRAINT_NAME}`");
                } catch (\Exception $e) {
                    // Ya no existe, continuar
                }
            }
        }

        // Modificar columna a nullable
        if (Schema::hasColumn('orders', 'dep_buy_id')) {
            DB::statement("ALTER TABLE orders MODIFY COLUMN dep_buy_id BIGINT UNSIGNED NULL");
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('orders', 'dep_buy_id')) {
            DB::statement("ALTER TABLE orders MODIFY COLUMN dep_buy_id BIGINT UNSIGNED NOT NULL");
        }
    }
};