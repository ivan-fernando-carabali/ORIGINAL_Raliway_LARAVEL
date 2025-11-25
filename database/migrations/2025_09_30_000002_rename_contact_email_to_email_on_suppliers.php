<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            if (Schema::hasColumn('suppliers', 'contact_email') && !Schema::hasColumn('suppliers', 'email')) {
                $table->renameColumn('contact_email', 'email');
            }
            // Ajustar longitud si fuese necesario
            if (Schema::hasColumn('suppliers', 'email')) {
                $table->string('email', 100)->change();
            }
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table) {
            if (Schema::hasColumn('suppliers', 'email') && !Schema::hasColumn('suppliers', 'contact_email')) {
                $table->renameColumn('email', 'contact_email');
            }
            if (Schema::hasColumn('suppliers', 'contact_email')) {
                $table->string('contact_email', 100)->change();
            }
        });
    }
};


