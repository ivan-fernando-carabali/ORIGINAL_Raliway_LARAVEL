<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // category_id
            if (!Schema::hasColumn('products', 'category_id')) {
                $table->foreignId('category_id')->nullable()
                    ->constrained('categories')
                    ->nullOnDelete()
                    ->cascadeOnUpdate();
            }

            // reference
            if (!Schema::hasColumn('products', 'reference')) {
                $table->string('reference', 50)->nullable()->after('name');
            }

            // unit_measurement
            if (!Schema::hasColumn('products', 'unit_measurement')) {
                $table->string('unit_measurement', 10)->nullable()->after('reference');
            }

            // expiration_date
            if (!Schema::hasColumn('products', 'expiration_date')) {
                $table->date('expiration_date')->nullable()->after('batch');
            }

            // iconos
            foreach (['icono1', 'icono2', 'icono3'] as $index => $icon) {
                if (!Schema::hasColumn('products', $icon)) {
                    $afterColumn = $index === 0 ? 'expiration_date' : 'icono' . $index;
                    $table->string($icon, 100)->nullable()->after($afterColumn);
                }
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Eliminar iconos si existen
            foreach (['icono3', 'icono2', 'icono1'] as $icon) {
                if (Schema::hasColumn('products', $icon)) {
                    $table->dropColumn($icon);
                }
            }

            // NO revertimos referencia, unit_measurement ni expiration_date
            // porque no podemos cambiar columnas existentes sin doctrine/dbal
        });
    }
};
