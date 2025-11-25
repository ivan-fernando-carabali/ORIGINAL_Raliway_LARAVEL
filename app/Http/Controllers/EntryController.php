<?php

namespace App\Http\Controllers;

use App\Services\EntryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;

class EntryController extends Controller
{
    protected EntryService $entryService;

    public function __construct(EntryService $entryService)
    {
        $this->middleware('auth:sanctum');
        $this->entryService = $entryService;
    }

    /**
     * 📄 Listar todas las entradas (OPTIMIZADO)
     */
    public function index(Request $request): JsonResponse
    {
        $limit = $request->get('limit', null);
        $orderBy = $request->get('order_by', 'created_at');
        $order = $request->get('order', 'desc');
        
        $data = $this->entryService->getAllEntries($limit, $orderBy, $order);

        return response()->json([
            'status'  => 'success',
            'message' => 'Listado de entradas obtenido correctamente.',
            'data'    => $data,
        ]);
    }

    /**
     * 📊 Resumen optimizado de lotes por producto
     */
    public function lotsSummary(): JsonResponse
    {
        try {
            // Query optimizado: agrupar directamente en la base de datos
            $lotsSummary = DB::table('entries')
                ->select(
                    'product_id',
                    DB::raw('UPPER(TRIM(COALESCE(lot, "SIN_LOTE"))) as lot'),
                    DB::raw('SUM(quantity) as total_quantity')
                )
                ->whereNotNull('product_id')
                ->groupBy('product_id', DB::raw('UPPER(TRIM(COALESCE(lot, "SIN_LOTE")))'))
                ->get()
                ->map(function ($item) {
                    return [
                        'product_id' => $item->product_id,
                        'lot' => $item->lot,
                        'batch' => $item->lot,
                        'total_quantity' => (float) $item->total_quantity,
                        'stock' => (float) $item->total_quantity
                    ];
                });

            return response()->json([
                'status' => 'success',
                'message' => 'Resumen de lotes obtenido correctamente.',
                'data' => $lotsSummary
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al obtener resumen de lotes.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ➕ Crear una nueva entrada
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'product_id'        => 'required|exists:products,id',
                'quantity'          => 'required|numeric|min:1',
                'unit'              => 'nullable|string|max:20',
                'lot'               => 'nullable|string|max:50',
                'supplier_id'       => 'required|exists:suppliers,id',
                'ubicacion_interna' => 'required|string|max:255',
                'min_stock'         => 'required|numeric|min:0',
            ]);

            $user = Auth::user();
            if (!$user) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Usuario no autenticado.',
                ], 401);
            }

            $entry = $this->entryService->createEntryWithInventoryAndUser($validated, $user->id);

            return response()->json([
                'status'  => 'success',
                'message' => '✅ Entrada registrada correctamente.',
                'data'    => $entry,
            ], 201);

        } catch (Exception $e) {
            Log::error('❌ Error al registrar entrada: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            if (config('app.debug')) {
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Error al registrar la entrada.',
                    'error'   => $e->getMessage(),
                ], 500);
            }

            return response()->json([
                'status'  => 'error',
                'message' => 'Error al registrar la entrada.',
            ], 500);
        }
    }

    /**
     * 🔍 Mostrar una entrada
     */
    public function show(int $id): JsonResponse
    {
        $entry = $this->entryService->getEntryById($id);

        return response()->json([
            'status'  => 'success',
            'message' => 'Detalles de la entrada obtenidos correctamente.',
            'data'    => $entry,
        ]);
    }

    /**
     * ✏️ Actualizar una entrada existente
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'quantity'          => 'sometimes|numeric|min:1',
            'unit'              => 'sometimes|string|max:20',
            'lot'               => 'sometimes|string|max:50',
            'ubicacion_interna' => 'sometimes|string|max:255',
            'min_stock'         => 'sometimes|numeric|min:0',
        ]);

        $entry = $this->entryService->updateEntry($validated, $id);

        return response()->json([
            'status'  => 'success',
            'message' => 'Entrada actualizada exitosamente.',
            'data'    => $entry,
        ]);
    }

    /**
     * 🗑️ Eliminar una entrada
     */
    public function destroy(int $id): JsonResponse
    {
        $this->entryService->deleteEntry($id);

        return response()->json([
            'status'  => 'success',
            'message' => 'Entrada eliminada correctamente.',
        ]);
    }

    /**
     * 📊 Resumen de entradas
     */
    public function summary(): JsonResponse
    {
        $summary = $this->entryService->getSummary();

        return response()->json([
            'status'  => 'success',
            'message' => 'Resumen de entradas obtenido correctamente.',
            'data'    => $summary,
        ]);
    }

    /**
     * 📦 Datos para selects de formulario (productos, proveedores)
     */
    public function formData(): JsonResponse
    {
        try {
            $data = $this->entryService->formData();

            return response()->json([
                'status'      => 'success',
                'message'     => 'Datos del formulario obtenidos correctamente.',
                'productos'   => $data['products'],
                'proveedores' => $data['suppliers'],
            ]);
        } catch (Exception $e) {
            Log::error('❌ Error al obtener formData: ' . $e->getMessage());

            return response()->json([
                'status'  => 'error',
                'message' => 'Error al obtener datos del formulario.',
            ], 500);
        }
    }
}
