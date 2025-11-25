<?php

namespace App\Services;


use App\Models\Inventory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\AlertService;

class InventoryService
{
    /**
     * 🆕 Crear o actualizar inventario (automático)
     */
    public function createOrUpdateInventory(Request $request)
    {
        $validated = $request->validate([
            'product_id'        => 'required|exists:products,id',
            'lot'               => 'nullable|string|max:255',
            'stock'             => 'required|numeric|min:0',
            'min_stock'         => 'nullable|numeric|min:0',
            'ubicacion_interna' => 'nullable|string|max:255',
            'unit'              => 'nullable|string|max:50',
        ]);

        try {
            $user = Auth::user();

            $inventory = DB::transaction(function () use ($validated, $user) {
                // Buscar si ya existe inventario del mismo producto y lote
                $existing = Inventory::where('product_id', $validated['product_id'])
                    ->where('lot', $validated['lot'])
                    ->first();

                if ($existing) {
                    // 🔁 Actualizar stock existente
                    $existing->increment('stock', $validated['stock']);
                    $existing->min_stock = $validated['min_stock'] ?? $existing->min_stock;
                    $existing->ubicacion_interna = $validated['ubicacion_interna'] ?? $existing->ubicacion_interna;
                    $existing->unit = $validated['unit'] ?? $existing->unit;
                    $existing->user_id = $user->id;
                    $existing->save();

                    Log::info("📈 Inventario actualizado (ID: {$existing->id}, stock: {$existing->stock})");

                    // 🔔 Verificar y resolver/crear alertas automáticamente
                    app(AlertService::class)->checkStock($existing);

                    return $existing;
                }

                // 🆕 Crear nuevo registro
                $inventory = Inventory::create([
                    'product_id'        => $validated['product_id'],
                    'lot'               => $validated['lot'] ?? null,
                    'stock'             => $validated['stock'],
                    'min_stock'         => $validated['min_stock'] ?? 0,
                    'ubicacion_interna' => $validated['ubicacion_interna'] ?? null,
                    'unit'              => $validated['unit'] ?? null,
                    'user_id'           => $user->id,
                ]);

                Log::info("🆕 Inventario creado (ID: {$inventory->id}, stock: {$inventory->stock})");

                // 🔔 Verificar y resolver/crear alertas automáticamente
                app(AlertService::class)->checkStock($inventory);

                return $inventory;
            });

            return response()->json([
                'status'  => 'success',
                'message' => 'Inventario creado o actualizado correctamente.',
                'data'    => $inventory->load('product', 'user'),
            ], 201);

        } catch (\Exception $e) {
            Log::error('❌ Error al crear/actualizar inventario: ' . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => 'Error al crear o actualizar inventario.',
                'details' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * 🔄 Ajustar stock (entrada o salida)
     */
    public function adjustStock(Request $request, $id)
    {
        $validated = $request->validate([
            'quantity'  => 'required|numeric|min:1',
            'operation' => 'required|in:add,subtract',
        ]);

        try {
            $inventory = Inventory::findOrFail($id);

            DB::transaction(function () use ($inventory, $validated) {
                if ($validated['operation'] === 'add') {
                    $inventory->stock += $validated['quantity'];
                    Log::info("📈 Entrada de {$validated['quantity']} unidades en inventario ID {$inventory->id} (nuevo stock: {$inventory->stock})");
                } else {
                    $inventory->stock = max(0, $inventory->stock - $validated['quantity']);
                    Log::info("📉 Salida de {$validated['quantity']} unidades en inventario ID {$inventory->id} (nuevo stock: {$inventory->stock})");
                }

                $inventory->save();

                // 🔔 Verificar y resolver/crear alertas automáticamente
                app(AlertService::class)->checkStock($inventory);
            });

            return response()->json([
                'status'  => 'success',
                'message' => 'Stock ajustado correctamente.',
                'data'    => $inventory->load('product'),
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error al ajustar stock: ' . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => 'Error al ajustar el stock.',
                'details' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * 🗑️ Eliminar inventario
     */
    public function deleteInventory($id)
    {
        try {
            $inventory = Inventory::findOrFail($id);
            $inventory->delete();

            Log::warning("🗑️ Inventario ID {$id} eliminado.");

            return response()->json([
                'status'  => 'success',
                'message' => 'Inventario eliminado correctamente.',
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error al eliminar inventario: ' . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => 'Error al eliminar el inventario.',
            ], 500);
        }
    }

    /**
     * 📊 Resumen de inventario
     */
    public function getSummary()
    {
        try {
            $count = Inventory::count();
            $total = Inventory::sum('stock');
            $lowStock = Inventory::whereColumn('stock', '<', 'min_stock')->count();

            return [
                'total_inventories' => $count,
                'total_stock'       => $total,
                'low_stock_count'   => $lowStock,
            ];
        } catch (\Exception $e) {
            Log::error('❌ Error al obtener resumen de inventario: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * 📋 Listar todos los inventarios
     */
    public function getAllInventories()
    {
        try {
            $inventories = Inventory::with(['product', 'user'])
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'status' => 'success',
                'data'   => $inventories,
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error al listar inventarios: ' . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => 'Error al obtener inventarios.',
            ], 500);
        }
    }

    /**
     * 🔍 Obtener un inventario por ID
     */
    public function getInventoryById($id)
    {
        try {
            $inventory = Inventory::with(['product', 'user'])->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data'   => $inventory,
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error al obtener inventario: ' . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => 'Inventario no encontrado.',
            ], 404);
        }
    }
}
