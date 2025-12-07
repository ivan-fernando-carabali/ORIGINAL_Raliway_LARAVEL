<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id(); // Primary Key
            $table->string('name', 100);
            $table->string('tax_id', 20)->unique()->comment('NIT/RUC/VAT ID');
            $table->string('address', 150);
$table->string('email')->nullable(); // Permite que no tenga valor
            $table->string('phone', 20);
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
