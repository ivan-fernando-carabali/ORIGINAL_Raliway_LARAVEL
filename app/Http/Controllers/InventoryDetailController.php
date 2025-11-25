<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\InventoryDetail;

class InventoryDetailController 
{
    // Listar todos los registros
    public function index()
    {
        return InventoryDetail::all();
    }

    // Crear nuevo registro
    public function store(Request $request)
    {
        $validated = $request->validate([
            'inventory_id' => 'required|exists:inventories,id',
            'product_id' => 'required|exists:products,id',
            'location_id' => 'required|exists:locations,id',
            'current_stock' => 'required|integer',
            'min_threshold' => 'sometimes|integer',
        ]);

        $inventoryDetail = InventoryDetail::create($validated);
        return response()->json($inventoryDetail, 201);
    }

    // Mostrar un registro
    public function show(InventoryDetail $inventoryDetail)
    {
        return $inventoryDetail;
    }

    // Actualizar un registro
    public function update(Request $request, InventoryDetail $inventoryDetail)
    {
        $validated = $request->validate([
            'inventory_id' => 'sometimes|exists:inventories,id',
            'product_id' => 'sometimes|exists:products,id',
            'location_id' => 'sometimes|exists:locations,id',
            'current_stock' => 'sometimes|integer',
            'min_threshold' => 'sometimes|integer',
        ]);

        $inventoryDetail->update($validated);
        return response()->json($inventoryDetail);
    }

    // Eliminar un registro
    public function destroy(InventoryDetail $inventoryDetail)
    {
        $inventoryDetail->delete();
        return response()->noContent();
    }
}
