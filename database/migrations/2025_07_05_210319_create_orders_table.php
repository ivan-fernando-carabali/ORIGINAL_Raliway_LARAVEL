<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->onDelete('set null');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->foreignId('inventory_id')->nullable()->constrained('inventories')->onDelete('set null');
            $table->foreignId('alert_id')->nullable()->constrained('alerts')->onDelete('set null');
            $table->foreignId('dep_buy_id')->nullable()->constrained('dep_buys')->onDelete('set null'); // NULLABLE

            $table->integer('quantity');
            $table->string('supplier_email')->nullable();

            // ❗️ Corrección: DATE sin default (MySQL NO permite CURRENT_DATE como default)
            $table->date('date')->nullable(false);

            // Cambiado de 'state' a 'status'
            $table->string('status')->default('pendiente');

            $table->text('notes')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('received_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
