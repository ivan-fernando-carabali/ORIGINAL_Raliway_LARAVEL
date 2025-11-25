<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Hacer columnas opcionales (requiere doctrine/dbal para change())
            $table->foreignId('category_id')->nullable()->change();
            $table->string('reference', 50)->nullable()->change();
            $table->string('unit_measurement', 10)->nullable()->change();

            // Cambiar a date y permitir null
            $table->date('expiration_date')->nullable()->change();

            // Agregar iconos
            if (!Schema::hasColumn('products', 'icono1')) {
                $table->string('icono1', 100)->nullable()->after('expiration_date');
            }
            if (!Schema::hasColumn('products', 'icono2')) {
                $table->string('icono2', 100)->nullable()->after('icono1');
            }
            if (!Schema::hasColumn('products', 'icono3')) {
                $table->string('icono3', 100)->nullable()->after('icono2');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Eliminar iconos
            if (Schema::hasColumn('products', 'icono3')) {
                $table->dropColumn('icono3');
            }
            if (Schema::hasColumn('products', 'icono2')) {
                $table->dropColumn('icono2');
            }
            if (Schema::hasColumn('products', 'icono1')) {
                $table->dropColumn('icono1');
            }

            // Revertir nulabilidad (requiere doctrine/dbal para change())
            $table->foreignId('category_id')->nullable(false)->change();
            $table->string('reference', 50)->nullable(false)->change();
            $table->string('unit_measurement', 10)->nullable(false)->change();

            // Volver a dateTime no nulo
            $table->dateTime('expiration_date')->nullable(false)->change();
        });
    }
};


