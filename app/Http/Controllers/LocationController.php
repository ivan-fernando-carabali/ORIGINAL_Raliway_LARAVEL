<?php

namespace App\Http\Controllers;

use App\Services\LocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Exception;

class LocationController extends Controller
{
    protected LocationService $locationService;

    public function __construct(LocationService $locationService)
    {
        $this->middleware('auth:sanctum');
        $this->locationService = $locationService;
    }

    /**
     * 📄 Listar todas las ubicaciones
     *
     * Soporta:
     * - ?included=warehouse
     * - ?filter[aisle]=A1
     * - ?sort=-aisle
     * - ?perPage=10
     */
    public function index(): JsonResponse
    {
        try {
            $locations = $this->locationService->getAllLocations();

            return response()->json([
                'status'  => 'success',
                'message' => 'Ubicaciones obtenidas correctamente.',
                'data'    => $locations,
            ]);
        } catch (Exception $e) {
            Log::error('❌ Error al obtener ubicaciones: ' . $e->getMessage());

            return response()->json([
                'status'  => 'error',
                'message' => 'Error al obtener ubicaciones.',
            ], 500);
        }
    }

    /**
     * ➕ Crear nueva ubicación
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'warehouse_id' => 'required|exists:warehouses,id',
                'aisle'        => 'required|string|max:20',
                'row'          => 'required|string|max:20',
                'level'        => 'nullable|string|max:20',
                'capacity'     => 'required|string|max:100',
            ]);

            $location = $this->locationService->createLocation($validated);

            return response()->json([
                'status'  => 'success',
                'message' => '✅ Ubicación creada correctamente.',
                'data'    => $location,
            ], 201);

        } catch (Exception $e) {
            Log::error('❌ Error al crear ubicación: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            if (config('app.debug')) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Error al crear la ubicación.',
                    'error'   => $e->getMessage(),
                ], 500);
            }

            return response()->json([
                'status'  => 'error',
                'message' => 'Error al crear la ubicación.',
            ], 500);
        }
    }

    /**
     * 🔍 Mostrar una ubicación específica
     */
    public function show(int $id): JsonResponse
    {
        try {
            $location = $this->locationService->getLocationById($id);

            return response()->json([
                'status'  => 'success',
                'message' => 'Ubicación obtenida correctamente.',
                'data'    => $location,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Ubicación no encontrada.',
            ], 404);
        }
    }

    /**
     * ✏️ Actualizar ubicación
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'warehouse_id' => 'sometimes|exists:warehouses,id',
                'aisle'        => 'sometimes|string|max:20',
                'row'          => 'sometimes|string|max:20',
                'level'        => 'nullable|string|max:20',
                'capacity'     => 'sometimes|string|max:100',
            ]);

            $location = $this->locationService->updateLocation($validated, $id);

            return response()->json([
                'status'  => 'success',
                'message' => 'Ubicación actualizada correctamente.',
                'data'    => $location,
            ]);

        } catch (Exception $e) {
            Log::error('❌ Error al actualizar ubicación: ' . $e->getMessage());

            return response()->json([
                'status'  => 'error',
                'message' => 'Error al actualizar la ubicación.',
            ], 500);
        }
    }

    /**
     * 🗑️ Eliminar ubicación
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->locationService->deleteLocation($id);

            return response()->json([
                'status'  => 'success',
                'message' => 'Ubicación eliminada correctamente.',
            ]);

        } catch (Exception $e) {
            Log::error('❌ Error al eliminar ubicación: ' . $e->getMessage());

            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * 📊 Obtener estadísticas de ubicaciones
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = $this->locationService->getLocationStats();

            return response()->json([
                'status'  => 'success',
                'message' => 'Estadísticas obtenidas correctamente.',
                'data'    => $stats,
            ]);
        } catch (Exception $e) {
            Log::error('❌ Error al obtener estadísticas: ' . $e->getMessage());

            return response()->json([
                'status'  => 'error',
                'message' => 'Error al obtener estadísticas.',
            ], 500);
        }
    }

    /**
     * 🏢 Obtener ubicaciones por almacén
     *
     * GET /api/locations/by-warehouse/{warehouseId}
     */
    public function byWarehouse(int $warehouseId): JsonResponse
    {
        try {
            $locations = $this->locationService->getLocationsByWarehouse($warehouseId);

            return response()->json([
                'status'  => 'success',
                'message' => 'Ubicaciones del almacén obtenidas correctamente.',
                'data'    => $locations,
            ]);
        } catch (Exception $e) {
            Log::error('❌ Error al obtener ubicaciones por almacén: ' . $e->getMessage());

            return response()->json([
                'status'  => 'error',
                'message' => 'Error al obtener ubicaciones del almacén.',
            ], 500);
        }
    }

    /**
     * ✅ Verificar disponibilidad de ubicación
     *
     * GET /api/locations/{id}/availability
     */
    public function availability(int $id): JsonResponse
    {
        try {
            $availability = $this->locationService->checkAvailability($id);

            return response()->json([
                'status'  => 'success',
                'message' => 'Disponibilidad verificada correctamente.',
                'data'    => $availability,
            ]);
        } catch (Exception $e) {
            Log::error('❌ Error al verificar disponibilidad: ' . $e->getMessage());

            return response()->json([
                'status'  => 'error',
                'message' => 'Error al verificar disponibilidad.',
            ], 500);
        }
    }
}
