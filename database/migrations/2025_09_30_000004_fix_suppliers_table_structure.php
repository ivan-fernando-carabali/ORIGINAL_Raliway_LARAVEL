<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            // Cambiar contact_email a email
            if (Schema::hasColumn('suppliers', 'contact_email')) {
                $table->renameColumn('contact_email', 'email');
            }

            // Hacer tax_id nullable
            if (Schema::hasColumn('suppliers', 'tax_id')) {
                $table->string('tax_id', 20)->nullable()->change();
            }

            // Hacer address nullable
            if (Schema::hasColumn('suppliers', 'address')) {
                $table->string('address', 150)->nullable()->change();
            }

            // Hacer phone nullable
            if (Schema::hasColumn('suppliers', 'phone')) {
                $table->string('phone', 20)->nullable()->change();
            }

            // Hacer email nullable
            if (Schema::hasColumn('suppliers', 'email')) {
                $table->string('email', 100)->nullable()->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            // Revertir cambios
            if (Schema::hasColumn('suppliers', 'email')) {
                $table->renameColumn('email', 'contact_email');
            }

            if (Schema::hasColumn('suppliers', 'tax_id')) {
                $table->string('tax_id', 20)->nullable(false)->change();
            }

            if (Schema::hasColumn('suppliers', 'address')) {
                $table->string('address', 150)->nullable(false)->change();
            }

            if (Schema::hasColumn('suppliers', 'phone')) {
                $table->string('phone', 20)->nullable(false)->change();
            }

            if (Schema::hasColumn('suppliers', 'contact_email')) {
                $table->string('contact_email', 100)->nullable(false)->change();
            }
        });
    }
};
