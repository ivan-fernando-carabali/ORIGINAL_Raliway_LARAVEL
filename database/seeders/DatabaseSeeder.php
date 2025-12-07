<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Ejecutar el seeder de roles
        $this->call(RoleSeeder::class);

        // 2. Crear usuario admin sin causar Duplicates
        User::firstOrCreate(
            ['email' => 'test@example.com'], // Buscar por email

            // Si no existe, lo crea con estos valores:
            [
                'name' => 'Test User',
                'role_id' => 1,
                'password' => bcrypt('12345678'),
            ]
        );
    }
}
