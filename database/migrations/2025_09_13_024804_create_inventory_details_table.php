<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inventory_details', function (Blueprint $table) {
            $table->id();

            $table->foreignId('inventory_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('location_id')->constrained()->onDelete('cascade');

            $table->integer('current_stock')->default(0);      // Cantidad actual
            $table->integer('min_threshold')->default(5);      // Stock mínimo
            $table->integer('critical_threshold')->default(2); // Stock crítico
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_details');
    }
};
