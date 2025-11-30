<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecuta la migración.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            // Nombre y apellido obligatorios
            $table->string('name');
        $table->string('lastname')->nullable();


            // Correo único y teléfono opcional
            $table->string('email')->unique();
            $table->string('telephone')->nullable();

            // Relación con roles, nullable, elimina en cascade si el rol se borra
            $table->foreignId('role_id')
                  ->nullable()
                  ->constrained('roles')
                  ->onDelete('cascade');

            // Contraseña obligatoria
            $table->string('password');

            // Verificación de email
            $table->timestamp('email_verified_at')->nullable();

            // Token de recordatorio
            $table->rememberToken();

            // Timestamps
            $table->timestamps();
        });
    }

    /**
     * Revertir la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
