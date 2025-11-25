<?php

namespace App\Services;

use App\Models\ProductExit;
use App\Models\Product;
use App\Models\Inventory;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductExitService
{
    /**
     * 📄 Listar todas las salidas
     */
    public function getAllExits()
    {
        $exits = ProductExit::with(['product.category', 'user'])->get();

        $mapped = $exits->map(function ($exit) {
            return [
                'id'           => $exit->id,
                'producto'     => $exit->product->name ?? 'Producto desconocido',
                'categoria'    => $exit->product->category->name ?? 'Sin categoría',
                'usuario'      => $exit->user->name ?? 'Desconocido',
                'fecha'        => $exit->created_at ? $exit->created_at->format('d/m/Y') : null,
                'lote'         => $exit->lot ?? '',
                'cantidad'     => $exit->quantity . ' ' . ($exit->unit ?? ''),
                'inventory_id' => $exit->inventory_id,
            ];
        });

        return response()->json([
            'message' => 'Listado de salidas de productos',
            'data' => $mapped
        ]);
    }

    /**
     * ➕ Crear nueva salida
     */
    public function createExit(Request $request)
    {
        $validated = $request->validate([
            'product_id'   => 'required|exists:products,id',
            'quantity'     => 'required|integer|min:1',
            'unit'         => 'nullable|string|max:20',
            'lot'          => 'nullable|string|max:50',
            'user_id'      => 'required|exists:users,id',
            'inventory_id' => 'required|exists:inventories,id',
        ]);

        return DB::transaction(function () use ($validated) {
            $inventory = Inventory::findOrFail($validated['inventory_id']);

            if ($validated['quantity'] > $inventory->stock) {
                return response()->json([
                    'message' => 'No hay suficiente stock para esta salida.'
                ], 400);
            }

            $exit = ProductExit::create($validated);

            $inventory->stock -= $validated['quantity'];
            $inventory->save();

            return response()->json([
                'message' => 'Salida de producto creada exitosamente',
                'data' => $exit->load(['product', 'user'])
            ], 201);
        });
    }

    /**
     * 🔍 Mostrar una salida
     */
    public function getExitById($id)
    {
        $exit = ProductExit::with(['product.category', 'user'])->findOrFail($id);

        return response()->json([
            'message' => 'Detalles de la salida de producto',
            'data' => [
                'id'         => $exit->id,
                'producto'   => $exit->product->name ?? 'Producto desconocido',
                'categoria'  => $exit->product->category->name ?? 'Sin categoría',
                'usuario'    => $exit->user->name ?? 'Desconocido',
                'fecha'      => $exit->created_at ? $exit->created_at->format('d/m/Y') : null,
                'lote'       => $exit->lot ?? '',
                'cantidad'   => $exit->quantity . ' ' . ($exit->unit ?? ''),
            ]
        ]);
    }

    /**
     * ✏️ Actualizar una salida
     */
    public function updateExit(Request $request, $id)
    {
        $exit = ProductExit::findOrFail($id);

        $validated = $request->validate([
            'product_id'   => 'sometimes|exists:products,id',
            'quantity'     => 'sometimes|integer|min:1',
            'unit'         => 'sometimes|string|max:20',
            'lot'          => 'sometimes|string|max:50',
            'user_id'      => 'sometimes|exists:users,id',
            'inventory_id' => 'sometimes|exists:inventories,id',
        ]);

        return DB::transaction(function () use ($exit, $validated) {
            $inventory = Inventory::findOrFail($exit->inventory_id);

            if (isset($validated['quantity']) && $validated['quantity'] != $exit->quantity) {
                $diff = $validated['quantity'] - $exit->quantity;

                if ($diff > 0 && $diff > $inventory->stock) {
                    return response()->json([
                        'message' => 'No hay suficiente stock para aumentar la cantidad.'
                    ], 400);
                }

                $inventory->stock -= $diff;
                $inventory->save();
            }

            $exit->update($validated);

            return response()->json([
                'message' => 'Salida de producto actualizada exitosamente',
                'data' => $exit->load(['product', 'user'])
            ]);
        });
    }

    /**
     * 📊 Resumen de salidas
     */
    public function getSummary()
    {
        $count = ProductExit::count();
        $total = ProductExit::sum('quantity');
        $last  = ProductExit::latest()->value('created_at');

        return response()->json([
            'total_exits'     => $count,
            'total_quantity'  => $total,
            'last_exit_date'  => $last ? $last->format('Y-m-d H:i:s') : null,
        ]);
    }

    /**
     * 🗑️ Eliminar una salida
     */
    public function deleteExit($id)
    {
        $exit = ProductExit::findOrFail($id);

        return DB::transaction(function () use ($exit) {
            $inventory = Inventory::findOrFail($exit->inventory_id);
            $inventory->stock += $exit->quantity;
            $inventory->save();

            $exit->delete();

            return response()->json(['message' => 'Salida de producto eliminada exitosamente']);
        });
    }

    /**
     * 📦 Listas para selects
     */
    public function getFormData()
    {
        $productos = Product::select('id', 'name')->get();
        $usuarios  = User::select('id', 'name')->get();

        return response()->json([
            'productos' => $productos,
            'usuarios'  => $usuarios
        ]);
    }
}
