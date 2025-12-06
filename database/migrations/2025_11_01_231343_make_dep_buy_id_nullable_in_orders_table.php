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
        Schema::table('orders', function (Blueprint $table) {
            // Eliminar la foreign key constraint primero
            $table->dropForeign(['dep_buy_id']);
            
            // Hacer el campo nullable
            $table->foreignId('dep_buy_id')->nullable()->change();
            
            // Recrear la foreign key constraint con nullOnDelete
            $table->foreign('dep_buy_id')->references('id')->on('dep_buys')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Eliminar la foreign key constraint
            $table->dropForeign(['dep_buy_id']);
            
            // Hacer el campo no nullable nuevamente
            $table->foreignId('dep_buy_id')->nullable(false)->change();
            
            // Recrear la foreign key constraint original
            $table->foreign('dep_buy_id')->references('id')->on('dep_buys')->onDelete('cascade');
        });
    }
};
