<?php

namespace App\Services;

use App\Models\Location;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LocationService
{
    /**
     * 📄 Listar todas las ubicaciones con filtros, ordenamiento y paginación
     */
    public function getAllLocations()
    {
        return Location::included()
            ->filter()
            ->sort()
            ->getOrPaginate();
    }

    /**
     * 🔍 Obtener una ubicación por ID
     */
    public function getLocationById(int $id)
    {
        return Location::with('warehouse')->findOrFail($id);
    }

    /**
     * ➕ Crear nueva ubicación
     */
    public function createLocation(array $data)
    {
        return DB::transaction(function () use ($data) {
            Log::info('📥 Creando nueva ubicación:', $data);

            $location = Location::create($data);

            Log::info("✅ Ubicación creada con ID {$location->id}");

            return $location->load('warehouse');
        });
    }

    /**
     * ✏️ Actualizar una ubicación existente
     */
    public function updateLocation(array $data, int $id)
    {
        return DB::transaction(function () use ($data, $id) {
            $location = Location::findOrFail($id);

            Log::info("📝 Actualizando ubicación ID {$id}:", $data);

            $location->update($data);

            Log::info("✅ Ubicación ID {$id} actualizada correctamente");

            return $location->load('warehouse');
        });
    }

    /**
     * 🗑️ Eliminar una ubicación
     */
    public function deleteLocation(int $id): void
    {
        DB::transaction(function () use ($id) {
            $location = Location::findOrFail($id);

            // Verificar si hay entradas asociadas
            if ($location->entries()->count() > 0) {
                throw new \Exception('No se puede eliminar. Hay entradas asociadas a esta ubicación.');
            }

            // Verificar si hay inventarios asociados
            if ($location->inventories()->count() > 0) {
                throw new \Exception('No se puede eliminar. Hay inventarios asociados a esta ubicación.');
            }

            $location->delete();

            Log::info("🗑️ Ubicación ID {$id} eliminada correctamente.");
        });
    }

    /**
     * 📊 Obtener ubicaciones por almacén
     */
    public function getLocationsByWarehouse(int $warehouseId)
    {
        return Location::where('warehouse_id', $warehouseId)
            ->orderBy('aisle')
            ->orderBy('row')
            ->get();
    }

    /**
     * 📈 Obtener estadísticas de ubicaciones
     */
    public function getLocationStats(): array
    {
        $total = Location::count();
        $byWarehouse = Location::select('warehouse_id', DB::raw('COUNT(*) as total'))
            ->with('warehouse:id,name')
            ->groupBy('warehouse_id')
            ->get();

        return [
            'total_locations' => $total,
            'by_warehouse'    => $byWarehouse,
        ];
    }

    /**
     * 🔍 Buscar ubicación específica por atributos
     */
    public function findLocationByAttributes(int $warehouseId, string $aisle, string $row)
    {
        return Location::where('warehouse_id', $warehouseId)
            ->where('aisle', $aisle)
            ->where('row', $row)
            ->first();
    }

    /**
     * ✅ Verificar disponibilidad de ubicación
     */
    public function checkAvailability(int $locationId): array
    {
        $location = Location::with(['inventories', 'warehouse'])->findOrFail($locationId);

        $totalStock = $location->inventories->sum('stock');

        return [
            'location_id'    => $location->id,
            'warehouse'      => $location->warehouse->name,
            'aisle'          => $location->aisle,
            'row'            => $location->row,
            'capacity'       => $location->capacity,
            'current_stock'  => $totalStock,
            'is_available'   => true, // Puedes agregar lógica de capacidad aquí
        ];
    }
}
