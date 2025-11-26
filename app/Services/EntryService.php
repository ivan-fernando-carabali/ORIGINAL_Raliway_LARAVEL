<?php

namespace App\Services;

use App\Models\Entry;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Inventory;
use App\Services\AlertService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EntryService
{
    protected ?AlertService $alertService;

    public function __construct(AlertService $alertService = null)
    {
        $this->alertService = $alertService;
    }

    /**
     * 📄 Listar todas las entradas (OPTIMIZADO)
     */
    public function getAllEntries($limit = null, $orderBy = 'created_at', $order = 'desc')
    {
        $query = Entry::query();
        
        // Eager loading optimizado
        $query->with([
            'product:id,name,reference,category_id',
            'product.category:id,name',
            'supplier:id,name',
            'warehouse:id,name',
            'location:id,name',
            'user:id,name,lastname'
        ]);
        
        // Aplicar límite si se especifica
        if ($limit && is_numeric($limit) && $limit > 0) {
            $query->limit((int) $limit);
        }
        
        // Ordenar
        if (in_array($orderBy, ['created_at', 'updated_at', 'quantity', 'product_id'])) {
            $query->orderBy($orderBy, $order === 'asc' ? 'asc' : 'desc');
        }
        
        $entries = $query->get();

        return $entries->map(function ($entry) {
            return [
                'id'                => $entry->id,
                'product_id'        => $entry->product_id,
                'producto'          => $entry->product->name ?? 'Producto desconocido',
                'categoria'         => $entry->product->category->name ?? 'Sin categoría',
                'proveedor'         => $entry->supplier->name ?? 'Desconocido',
                'supplier'          => $entry->supplier,
                'almacen'           => $entry->warehouse->name ?? 'Sin almacén',
                'warehouse'         => $entry->warehouse,
                'ubicacion'         => $entry->location->name ?? 'Sin ubicación',
                'location'          => $entry->location,
                'fecha'             => $entry->created_at?->format('d/m/Y'),
                'created_at'        => $entry->created_at?->toDateTimeString(),
                'lote'              => $entry->lot ?? '',
                'lot'               => $entry->lot ?? '',
                'batch'             => $entry->lot ?? '',
                'cantidad'          => $entry->quantity . ' ' . ($entry->unit ?? ''),
                'quantity'          => $entry->quantity,
                'unit'              => $entry->unit,
                'stock'             => $entry->stock,
                'min_stock'         => $entry->min_stock,
                'user'              => $entry->user ? [
                    'id' => $entry->user->id,
                    'name' => $entry->user->name,
                    'lastname' => $entry->user->lastname ?? '',
                ] : null,
            ];
        });
    }

    /**
     * ➕ Crear nueva entrada y gestionar inventario automáticamente
     */
    public function createEntryWithInventoryAndUser(array $data, int $userId)
    {
        return DB::transaction(function () use ($data, $userId) {
            // Preparar datos
            $data['user_id'] = $userId;
            $data['stock'] = $data['quantity'] ?? 0;
            $data['lot'] = $data['lot'] ?? 'SIN_LOTE';

            Log::info('📥 Creando entrada con datos:', $data);

            // Validar que existan las relaciones requeridas
            if (!isset($data['location_id'])) {
                // Si no hay location_id, intentar crear/obtener una ubicación por defecto
                $defaultLocation = DB::table('locations')
                    ->where('warehouse_id', $data['warehouse_id'])
                    ->where('name', 'LIKE', '%General%')
                    ->orWhere('name', 'LIKE', '%Principal%')
                    ->first();

                if (!$defaultLocation) {
                    throw new \Exception('No se encontró una ubicación válida para este almacén. Por favor, cree una ubicación primero.');
                }
                
                $data['location_id'] = $defaultLocation->id;
                Log::info("📍 Ubicación por defecto asignada: {$defaultLocation->name}");
            }

            // Crear entrada
            $entry = Entry::create($data);
            Log::info("✅ Entrada creada con ID {$entry->id}");

            // 🔗 Asociar proveedor al producto si no está asociado
            if (!empty($data['supplier_id'])) {
                $product = Product::find($entry->product_id);
                if ($product) {
                    $isAssociated = DB::table('product_supplier')
                        ->where('product_id', $product->id)
                        ->where('supplier_id', $data['supplier_id'])
                        ->exists();
                    
                    if (!$isAssociated) {
                        $product->suppliers()->attach($data['supplier_id'], [
                            'unit_cost' => 0,
                            'created_at' => now(),
                            'updated_at' => now()
                        ]);
                        Log::info("🔗 Proveedor {$data['supplier_id']} asociado al producto {$product->id}");
                    }
                }
            }

            // 📦 Gestionar inventario
            $inventory = Inventory::where('product_id', $entry->product_id)
                                  ->where('lot', $entry->lot)
                                  ->where('warehouse_id', $entry->warehouse_id)
                                  ->first();

            if ($inventory) {
                // Actualizar stock existente
                $inventory->stock += $entry->quantity;
                if (isset($entry->min_stock) && $entry->min_stock > 0) {
                    $inventory->min_stock = $entry->min_stock;
                }
                $inventory->save();

                Log::info("📦 Inventario actualizado: producto ID {$entry->product_id}, stock {$inventory->stock}");
            } else {
                // Crear nuevo inventario
                $inventory = Inventory::create([
                    'product_id'   => $entry->product_id,
                    'lot'          => $entry->lot,
                    'stock'        => $entry->quantity,
                    'min_stock'    => $entry->min_stock ?? 0,
                    'warehouse_id' => $entry->warehouse_id,
                    'location_id'  => $entry->location_id,
                    'user_id'      => $userId,
                ]);
                Log::info("🆕 Nuevo inventario creado para producto ID {$entry->product_id}");
            }

            // ⚠️ Gestionar alertas
            if ($this->alertService) {
                $this->alertService->resolvePendingAlertsForProduct($entry->product_id);
                $this->alertService->checkStock($inventory);
            }

            return $entry->load(['product', 'supplier', 'warehouse', 'location']);
        });
    }

    /**
     * 🔍 Obtener una entrada por ID
     */
    public function getEntryById(int $id)
    {
        $entry = Entry::with([
            'product.category', 
            'supplier', 
            'warehouse', 
            'location'
        ])->findOrFail($id);

        return [
            'id'          => $entry->id,
            'producto'    => $entry->product->name ?? 'Producto desconocido',
            'categoria'   => $entry->product->category->name ?? 'Sin categoría',
            'proveedor'   => $entry->supplier->name ?? 'Desconocido',
            'almacen'     => $entry->warehouse->name ?? 'Sin almacén',
            'ubicacion'   => $entry->location->name ?? 'Sin ubicación',
            'fecha'       => $entry->created_at?->format('d/m/Y'),
            'lote'        => $entry->lot ?? '',
            'cantidad'    => $entry->quantity . ' ' . ($entry->unit ?? ''),
            'stock'       => $entry->stock,
            'min_stock'   => $entry->min_stock,
        ];
    }

    /**
     * ✏️ Actualizar una entrada
     */
    public function updateEntry(array $data, int $id)
    {
        return DB::transaction(function () use ($data, $id) {
            $entry = Entry::findOrFail($id);
            $oldQuantity = $entry->quantity;
            $entry->update($data);

            // Actualizar inventario si cambió cantidad o lote
            $inventory = Inventory::where('product_id', $entry->product_id)
                                  ->where('lot', $entry->lot)
                                  ->where('warehouse_id', $entry->warehouse_id)
                                  ->first();

            if ($inventory && isset($data['quantity'])) {
                $inventory->stock += $data['quantity'] - $oldQuantity;
                $inventory->save();

                if ($this->alertService) {
                    $this->alertService->checkStock($inventory);
                }
            }

            return $entry->load(['product', 'supplier', 'warehouse', 'location']);
        });
    }

    /**
     * 🗑️ Eliminar una entrada
     */
    public function deleteEntry(int $id): void
    {
        DB::transaction(function () use ($id) {
            $entry = Entry::findOrFail($id);

            // Ajustar inventario
            $inventory = Inventory::where('product_id', $entry->product_id)
                                  ->where('lot', $entry->lot)
                                  ->where('warehouse_id', $entry->warehouse_id)
                                  ->first();

            if ($inventory) {
                $inventory->stock -= $entry->quantity;
                if ($inventory->stock < 0) {
                    $inventory->stock = 0;
                }
                $inventory->save();

                if ($this->alertService) {
                    $this->alertService->checkStock($inventory);
                }
            }

            $entry->delete();
            Log::info("🗑️ Entrada ID {$id} eliminada correctamente.");
        });
    }

    /**
     * 📊 Resumen de entradas
     */
    public function getSummary(): array
    {
        $entries = Entry::select(
            DB::raw('COUNT(id) as total_entries'), 
            DB::raw('SUM(quantity) as total_quantity')
        )->first();
        
        $last = Entry::latest('created_at')->first();

        return [
            'count'      => (int) ($entries->total_entries ?? 0),
            'quantity'   => (float) ($entries->total_quantity ?? 0),
            'last_date'  => $last?->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * 📦 Datos para formularios
     */
    public function formData(): array
    {
        return [
            'products'   => Product::select('id', 'name')->get(),
            'suppliers'  => Supplier::select('id', 'name')->get(),
        ];
    }
}