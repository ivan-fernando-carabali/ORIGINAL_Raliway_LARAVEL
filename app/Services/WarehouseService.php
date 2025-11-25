<?php

namespace App\Services;

use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class WarehouseService
{
    /**
     * 📄 Listar todos los almacenes con filtros, includes, sort y paginación
     */
    public function getAllWarehouses($request)
    {
        return Warehouse::included()
                        ->filter()
                        ->sort()
                        ->getOrPaginate();
    }

    /**
     * 🔍 Obtener un almacén por ID
     */
    public function getWarehouseById(int $id)
    {
        return Warehouse::included()->findOrFail($id);
    }

    /**
     * ➕ Crear nuevo almacén
     */
    public function createWarehouse(array $data)
    {
        return DB::transaction(function () use ($data) {
            Log::info('📥 Creando nuevo almacén:', $data);
            $warehouse = Warehouse::create($data);
            Log::info("✅ Almacén creado con ID {$warehouse->id}");
            return $warehouse;
        });
    }

    /**
     * ✏️ Actualizar un almacén existente
     */
    public function updateWarehouse(array $data, int $id)
    {
        return DB::transaction(function () use ($data, $id) {
            $warehouse = Warehouse::findOrFail($id);
            Log::info("📝 Actualizando almacén ID {$id}:", $data);
            $warehouse->update($data);
            return $warehouse->load('internalLocations', 'entries', 'inventories');
        });
    }

    /**
     * 🗑️ Eliminar un almacén
     */
    public function deleteWarehouse(int $id): void
    {
        DB::transaction(function () use ($id) {
            $warehouse = Warehouse::findOrFail($id);

            if ($warehouse->internalLocations()->count() > 0) {
                throw new Exception('No se puede eliminar. Hay ubicaciones asociadas.');
            }
            if ($warehouse->entries()->count() > 0) {
                throw new Exception('No se puede eliminar. Hay entradas asociadas.');
            }
            if ($warehouse->inventories()->count() > 0) {
                throw new Exception('No se puede eliminar. Hay inventarios asociados.');
            }

            $warehouse->delete();
            Log::info("🗑️ Almacén ID {$id} eliminado correctamente.");
        });
    }

    /**
     * 📊 Obtener estadísticas de un almacén
     */
    public function getWarehouseStats(int $id): array
    {
        $warehouse = Warehouse::with(['internalLocations', 'inventories'])->findOrFail($id);

        return [
            'warehouse_id'      => $warehouse->id,
            'warehouse_name'    => $warehouse->name,
            'total_locations'   => $warehouse->internalLocations->count(),
            'total_inventories' => $warehouse->inventories->count(),
            'total_stock'       => $warehouse->inventories->sum('stock'),
            'capacity'          => $warehouse->capacity,
        ];
    }

    /**
     * 📋 Obtener almacenes con capacidad disponible
     */
    public function getAvailableWarehouses()
    {
        return Warehouse::whereHas('internalLocations', function ($query) {
            $query->whereDoesntHave('inventories');
        })->with('internalLocations')->get();
    }
}
