<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            // Hacer tax_id opcional (requiere doctrine/dbal para change())
            if (Schema::hasColumn('suppliers', 'tax_id')) {
                $table->string('tax_id', 20)->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            if (Schema::hasColumn('suppliers', 'tax_id')) {
                $table->string('tax_id', 20)->nullable(false)->change();
            }
        });
    }
};


