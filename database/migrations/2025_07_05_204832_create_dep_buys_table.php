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
        Schema::create('dep_buys', function (Blueprint $table) {
            $table->id();
            $table->string('name',25 );
            $table->string('addres',25);
            $table->string('email',25)->unique();
            $table->string('responsible',25);
            $table->string('telephone',15);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dep_buys');
    }
};
