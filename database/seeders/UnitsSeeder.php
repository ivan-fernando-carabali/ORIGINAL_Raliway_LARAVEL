<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Unit;

class UnitsSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            ['name' => 'Unidad', 'abbreviation' => 'UN'],
            ['name' => 'Kilogramo', 'abbreviation' => 'kg'],
            ['name' => 'Gramo', 'abbreviation' => 'g'],
            ['name' => 'Litro', 'abbreviation' => 'L'],
            ['name' => 'Mililitro', 'abbreviation' => 'ml'],
            ['name' => 'Metro', 'abbreviation' => 'm'],
            ['name' => 'Caja', 'abbreviation' => 'caja'],
            ['name' => 'Paquete', 'abbreviation' => 'paq'],
            ['name' => 'Docena', 'abbreviation' => 'doc'],
            ['name' => 'Pieza', 'abbreviation' => 'pza'],
        ];

        foreach ($units as $unit) {
            Unit::create($unit);
        }
    }
}