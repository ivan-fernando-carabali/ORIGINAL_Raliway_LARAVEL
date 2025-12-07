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
        Schema::create('alerts', function (Blueprint $table) {
            $table->id();

            // Relaciones
            $table->foreignId('inventory_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');

            // Tipo de alerta: bajo_stock o sin_stock
            $table->string('alert_type', 100)
                  ->default('bajo_stock')
                  ->comment('Valores permitidos: bajo_stock, sin_stock');

            // Estado: pendiente o resuelta
            $table->string('status', 100)
                  ->default('pendiente')
                  ->comment('Valores permitidos: pendiente, resuelta');

            // Información de la alerta
            $table->text('message');
            $table->dateTime('date');

            // Fecha de resolución
            $table->timestamp('resolved_at')->nullable();

            // Timestamps automáticos
            $table->timestamps();

            // Índices para mejorar performance
            $table->index(['product_id', 'status']);
            $table->index('alert_type');
            $table->index('date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alerts');
    }
};
