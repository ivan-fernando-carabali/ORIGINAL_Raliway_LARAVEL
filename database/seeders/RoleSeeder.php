<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name' => 'admin',
                'state' => true,
                'permission' => json_encode(['create', 'read', 'update', 'delete']),
            ],
            [
                'name' => 'empleado',
                'state' => true,
                'permission' => json_encode(['read', 'update']),
            ],
            [
                'name' => 'invitado',
                'state' => true,
                'permission' => json_encode(['read']),
            ],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(
                ['name' => $role['name']], // evita duplicados
                [
                    'state' => $role['state'],
                    'permission' => $role['permission'],
                ]
            );
        }
    }
}
