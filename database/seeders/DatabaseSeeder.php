<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Primero ejecutar el seeder de roles
        $this->call(RoleSeeder::class);

        // 2. Crear usuario de prueba con rol admin (id = 1)
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role_id' => 1, // Rol admin
            'password' => bcrypt('12345678'),
        ]);
    }
}
