<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UnitsController extends Controller
{
    public function index(): JsonResponse
    {
        $units = Unit::where('is_active', true)
                     ->orderBy('name')
                     ->get();

        return response()->json([
            'success' => true,
            'data' => $units
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:units',
            'abbreviation' => 'required|string|max:10|unique:units',
            'type' => 'nullable|string|in:peso,volumen,longitud,general',
        ]);

        $unit = Unit::create([
            'name' => $validated['name'],
            'abbreviation' => $validated['abbreviation'],
            'type' => $validated['type'] ?? 'general',
            'is_active' => true
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Unidad creada exitosamente',
            'data' => $unit
        ], 201);
    }

    public function initialize(Request $request): JsonResponse
    {
        $defaultUnits = [
            ['name' => 'Kilogramo', 'abbreviation' => 'kg', 'type' => 'peso'],
            ['name' => 'Gramo', 'abbreviation' => 'g', 'type' => 'peso'],
            ['name' => 'Litro', 'abbreviation' => 'L', 'type' => 'volumen'],
            ['name' => 'Mililitro', 'abbreviation' => 'ml', 'type' => 'volumen'],
            ['name' => 'Unidad', 'abbreviation' => 'u', 'type' => 'general'],
            ['name' => 'Caja', 'abbreviation' => 'cja', 'type' => 'general'],
            ['name' => 'Paquete', 'abbreviation' => 'paq', 'type' => 'general'],
            ['name' => 'Metro', 'abbreviation' => 'm', 'type' => 'longitud'],
            ['name' => 'Centímetro', 'abbreviation' => 'cm', 'type' => 'longitud'],
            ['name' => 'Pieza', 'abbreviation' => 'pza', 'type' => 'general'],
        ];

        $created = 0;
        foreach ($defaultUnits as $unitData) {
            Unit::firstOrCreate(
                ['abbreviation' => $unitData['abbreviation']],
                $unitData
            );
            $created++;
        }

        return response()->json([
            'success' => true,
            'message' => "Se inicializaron {$created} unidades",
            'data' => Unit::all()
        ]);
    }
}