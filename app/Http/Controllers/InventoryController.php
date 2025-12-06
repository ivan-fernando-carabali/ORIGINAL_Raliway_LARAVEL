<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\InventoryService;
use App\Services\AlertService;
use App\Models\Inventory;

class InventoryController extends Controller
{
    protected InventoryService $inventoryService;
    protected AlertService $alertService;

    public function __construct(InventoryService $inventoryService, AlertService $alertService)
    {
        $this->middleware('auth:sanctum');
        $this->inventoryService = $inventoryService;
        $this->alertService = $alertService;
    }

    /**
     * 📋 Listar todos los inventarios (OPTIMIZADO)
     */
    public function index(Request $request)
    {
        try {
            $query = Inventory::query();
            
            // Eager loading optimizado - solo cargar relaciones necesarias
            // Incluir proveedores del producto si se solicita
            $include = $request->get('include', '');
            if (str_contains($include, 'product.suppliers') || str_contains($include, 'product.supplier')) {
                $query->with(['product:id,name,reference,codigo_de_barras', 'product.suppliers:id,name', 'warehouse:id,name']);
            } else {
                $query->with(['product:id,name,reference,codigo_de_barras', 'warehouse:id,name']);
            }
            
            // Filtrar por stock mínimo si se especifica
            if ($request->has('stock_min') && $request->stock_min > 0) {
                $query->where('stock', '>=', $request->stock_min);
            }
            
            // Filtrar solo con stock positivo
            if ($request->has('stock_min') && $request->stock_min == 1) {
                $query->where('stock', '>', 0);
            }
            
            // Seleccionar solo campos necesarios si se especifica
            if ($request->has('fields')) {
                $fields = explode(',', $request->fields);
                $fields = array_map('trim', $fields);
                // Asegurar que siempre incluimos id y product_id
                $fields = array_merge(['id', 'product_id'], array_diff($fields, ['id', 'product_id']));
                $query->select($fields);
            }
            
            // Ordenar por stock descendente para mostrar primero los que tienen stock
            $query->orderBy('stock', 'desc');
            
            $inventories = $query->get();

            return response()->json([
                'status' => 'success',
                'message' => 'Inventarios obtenidos correctamente.',
                'data' => $inventories
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al obtener inventarios.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 👁️ Mostrar un inventario específico
     */
    public function show($id)
    {
        try {
            $inventory = Inventory::with(['product', 'warehouse'])->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => $inventory
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Inventario no encontrado.',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * 📦 Crear o actualizar inventario automáticamente
     */
    public function store(Request $request)
    {
        try {
            // Validación coherente con el modelo y servicio
            $validated = $request->validate([
                'product_id'        => 'required|exists:products,id',
                'warehouse_id'      => 'nullable|exists:warehouses,id',
                'lot'               => 'nullable|string|max:255',
                'stock'             => 'required|numeric|min:0',
                'min_stock'         => 'nullable|numeric|min:0',
                'ubicacion_interna' => 'nullable|string|max:255',
                'unit'              => 'nullable|string|max:50',
            ]);

            // Crear inventario usando el servicio
            $response = $this->inventoryService->createOrUpdateInventory(new Request($validated));

            // Obtener el inventario creado/actualizado
            $inventory = Inventory::with('product')->latest()->first();

            // 🚨 IMPORTANTE: Verificar stock y crear alertas automáticamente
            if ($inventory) {
                $this->alertService->checkStock($inventory);
            }

            return $response;
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al crear inventario.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ✏️ Actualizar inventario
     */
    public function update(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'product_id'        => 'sometimes|exists:products,id',
                'warehouse_id'      => 'nullable|exists:warehouses,id',
                'lot'               => 'nullable|string|max:255',
                'stock'             => 'sometimes|numeric|min:0',
                'min_stock'         => 'nullable|numeric|min:0',
                'ubicacion_interna' => 'nullable|string|max:255',
                'unit'              => 'nullable|string|max:50',
            ]);

            $inventory = Inventory::findOrFail($id);
            $inventory->update($validated);

            // 🚨 Verificar stock y actualizar alertas
            $this->alertService->checkStock($inventory);

            return response()->json([
                'status' => 'success',
                'message' => 'Inventario actualizado correctamente.',
                'data' => $inventory->load('product', 'warehouse')
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al actualizar inventario.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 🔄 Ajustar stock (entrada o salida)
     */
    public function adjustStock(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'quantity'  => 'required|numeric|min:1',
                'operation' => 'required|in:add,subtract',
            ]);

            // Ajustar stock usando el servicio
            $response = $this->inventoryService->adjustStock(new Request($validated), $id);

            // Obtener el inventario actualizado
            $inventory = Inventory::with('product')->find($id);

            // 🚨 Verificar stock después del ajuste
            if ($inventory) {
                $this->alertService->checkStock($inventory);
            }

            return $response;
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al ajustar stock.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 🗑️ Eliminar inventario
     */
    public function destroy($id)
    {
        try {
            return $this->inventoryService->deleteInventory($id);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al eliminar inventario.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 📊 Resumen general de inventario
     */
    public function summary()
    {
        try {
            $summary = $this->inventoryService->getSummary();

            return response()->json([
                'status'  => 'success',
                'message' => 'Resumen de inventario obtenido correctamente.',
                'data'    => $summary,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al obtener resumen.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
