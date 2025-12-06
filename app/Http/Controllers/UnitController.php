<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UnitController extends Controller
{
    /**
     * Lista de unidades de medida predefinidas
     */
    private $defaultUnits = [
        ['id' => 1, 'name' => 'Unidad', 'abbreviation' => 'und'],
        ['id' => 2, 'name' => 'Kilogramo', 'abbreviation' => 'kg'],
        ['id' => 3, 'name' => 'Gramo', 'abbreviation' => 'g'],
        ['id' => 4, 'name' => 'Litro', 'abbreviation' => 'L'],
        ['id' => 5, 'name' => 'Mililitro', 'abbreviation' => 'ml'],
        ['id' => 6, 'name' => 'Metro', 'abbreviation' => 'm'],
        ['id' => 7, 'name' => 'Centímetro', 'abbreviation' => 'cm'],
        ['id' => 8, 'name' => 'Pulgada', 'abbreviation' => 'in'],
        ['id' => 9, 'name' => 'Libra', 'abbreviation' => 'lb'],
        ['id' => 10, 'name' => 'Onza', 'abbreviation' => 'oz'],
        ['id' => 11, 'name' => 'Caja', 'abbreviation' => 'caja'],
        ['id' => 12, 'name' => 'Paquete', 'abbreviation' => 'paq'],
        ['id' => 13, 'name' => 'Docena', 'abbreviation' => 'doc'],
        ['id' => 14, 'name' => 'Par', 'abbreviation' => 'par'],
        ['id' => 15, 'name' => 'Rollo', 'abbreviation' => 'rollo'],
        ['id' => 16, 'name' => 'Bolsa', 'abbreviation' => 'bolsa'],
        ['id' => 17, 'name' => 'Galón', 'abbreviation' => 'gal'],
        ['id' => 18, 'name' => 'Pieza', 'abbreviation' => 'pza'],
        ['id' => 19, 'name' => 'Arroba', 'abbreviation' => '@'],
        ['id' => 20, 'name' => 'Tonelada', 'abbreviation' => 't'],
    ];

    /**
     * 📄 Listar todas las unidades de medida
     */
    public function index()
    {
        return response()->json([
            'status' => 'success',
            'message' => 'Unidades de medida obtenidas correctamente',
            'data' => $this->defaultUnits
        ]);
    }

    /**
     * ➕ Crear una nueva unidad de medida
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'abbreviation' => 'nullable|string|max:20',
        ]);

        // Crear nueva unidad con ID temporal
        $newUnit = [
            'id' => count($this->defaultUnits) + rand(100, 999),
            'name' => $request->name,
            'abbreviation' => $request->abbreviation ?? strtolower(substr($request->name, 0, 3)),
        ];

        return response()->json([
            'status' => 'success',
            'message' => '✅ Unidad de medida creada correctamente',
            'data' => $newUnit
        ], 201);
    }

    /**
     * 🔍 Mostrar una unidad específica
     */
    public function show($id)
    {
        $unit = collect($this->defaultUnits)->firstWhere('id', (int)$id);

        if (!$unit) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unidad de medida no encontrada'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $unit
        ]);
    }
}

