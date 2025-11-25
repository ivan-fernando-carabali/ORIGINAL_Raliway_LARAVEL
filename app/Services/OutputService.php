<?php

namespace App\Services;


use App\Models\Output;
use App\Models\Product;
use App\Models\Inventory;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OutputService
{
    /**
     * 📋 Listar todas las salidas
     */
    public function listAll()
    {
        return Output::with(['product.category', 'user', 'inventory'])
            ->latest()
            ->get()
            ->map(fn($o) => [
                'id'          => $o->id,
                'producto'    => $o->product->name ?? 'Producto desconocido',
                'categoria'   => $o->product->category->name ?? 'Sin categoría',
                'usuario'     => $o->user->name ?? 'Desconocido',
                'cantidad'    => "{$o->quantity} " . ($o->unit ?? ''),
                'lote'        => $o->lot ?? '',
                'inventario'  => $o->inventory->id ?? null,
                'fecha'       => $o->created_at?->format('Y-m-d H:i:s'),
                'motivo'      => $o->motivo ?? '',
            ]);
    }

    /**
     * ➕ Crear nueva salida (FIFO)
     */
    public function create(array $validated): array
    {
        try {
            return DB::transaction(function () use ($validated) {
                // Buscar inventario FIFO
                $query = Inventory::where('product_id', $validated['product_id'])
                    ->where('stock', '>', 0);

                if (!empty($validated['lot'])) {
                    $query->where('lot', $validated['lot']);
                }

                $inventory = $query->orderBy('created_at', 'asc')->first();

                if (!$inventory) {
                    return [
                        'error' => true,
                        'message' => 'No se encontró inventario disponible para este producto' .
                            (!empty($validated['lot']) ? ' con el lote especificado.' : '.'),
                    ];
                }

                if ($validated['quantity'] > $inventory->stock) {
                    return [
                        'error' => true,
                        'message' => "Stock insuficiente. Disponible: {$inventory->stock}, Solicitado: {$validated['quantity']}",
                    ];
                }

                // Crear salida
                $output = Output::create([
                    'product_id'   => $inventory->product_id,
                    'inventory_id' => $inventory->id,
                    'quantity'     => $validated['quantity'],
                    'unit'         => $validated['unit'] ?? null,
                    'lot'          => $inventory->lot,
                    'user_id'      => $validated['user_id'],
                    'motivo'     => $validated['motivo'] ?? null,

                ]);

                // Actualizar stock
                $inventory->decrement('stock', $validated['quantity']);

                return [
                    'error'   => false,
                    'data'    => $output->load(['product', 'user', 'inventory']),
                    'message' => 'Salida registrada correctamente.',
                ];
            });
        } catch (\Exception $e) {
            Log::error('❌ Error al crear salida', [
                'error' => $e->getMessage(),
                'data' => $validated,
            ]);

            return ['error' => true, 'message' => 'Error al registrar la salida: ' . $e->getMessage()];
        }
    }

    /**
     * 🔍 Buscar salida por ID
     */
    public function find(int $id): array
    {
        $output = Output::with(['product.category', 'user', 'inventory'])->find($id);

        return $output
            ? ['error' => false, 'data' => $output]
            : ['error' => true, 'message' => 'No se encontró la salida especificada.'];
    }

    /**
     * ✏️ Actualizar salida existente
     */
    public function update(int $id, array $validated): array
    {
        return DB::transaction(function () use ($id, $validated) {
            $output = Output::find($id);

            if (!$output) {
                return ['error' => true, 'message' => 'No se encontró la salida especificada.'];
            }

            $inventory = $output->inventory;
            if (!$inventory) {
                return ['error' => true, 'message' => 'Inventario asociado no encontrado.'];
            }

            // Reajuste de stock si cambia la cantidad
            if (isset($validated['quantity']) && $validated['quantity'] != $output->quantity) {
                $diff = $validated['quantity'] - $output->quantity;

                if ($diff > 0 && $diff > $inventory->stock) {
                    return ['error' => true, 'message' => 'No hay suficiente stock para aumentar la cantidad.'];
                }

                $inventory->stock -= $diff;
                $inventory->save();
            }

            $validated['user_id'] = Auth::id();
            $output->update($validated);

            return [
                'error'   => false,
                'data'    => $output->load(['product', 'user', 'inventory']),
                'message' => 'Salida actualizada correctamente.',
            ];
        });
    }

    /**
     * ❌ Eliminar salida y restaurar stock
     */
    public function delete(int $id): array
    {
        return DB::transaction(function () use ($id) {
            $output = Output::find($id);

            if (!$output) {
                return ['error' => true, 'message' => 'No se encontró la salida especificada.'];
            }

            if ($output->inventory) {
                $output->inventory->increment('stock', $output->quantity);
            }

            $output->delete();

            return ['error' => false, 'message' => 'Salida eliminada y stock restaurado correctamente.'];
        });
    }

    /**
     * 📊 Resumen de salidas
     */
    public function summary(): array
    {
        return [
            'total_salidas'  => Output::count(),
            'total_cantidad' => Output::sum('quantity'),
            'ultima_salida'  => optional(Output::latest('created_at')->first())->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * ⚙️ Datos para formularios
     */
    public function formData(): array
    {
        return [
            'productos'   => Product::select('id', 'name')->get(),
            'inventarios' => Inventory::select('id', 'product_id', 'stock', 'lot')
                ->where('stock', '>', 0)
                ->get(),
        ];
    }
}
