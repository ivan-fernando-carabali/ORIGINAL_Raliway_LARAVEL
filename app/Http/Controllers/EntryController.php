<?php

namespace App\Http\Controllers;

use App\Services\EntryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
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
        try {
            $limit = $request->get('limit', null);
            $orderBy = $request->get('order_by', 'created_at');
            $order = $request->get('order', 'desc');
            
            $data = $this->entryService->getAllEntries($limit, $orderBy, $order);

            return response()->json([
                'status'  => 'success',
                'message' => 'Listado de entradas obtenido correctamente.',
                'data'    => $data,
            ]);
        } catch (Exception $e) {
            Log::error('❌ Error en index de entradas: ' . $e->getMessage());
            
            return response()->json([
                'status'  => 'error',
                'message' => 'Error al obtener las entradas.',
                'error'   => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * 📊 Resumen optimizado de lotes por producto
     */
    public function lotsSummary(): JsonResponse
    {
        try {
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
        } catch (Exception $e) {
            Log::error('❌ Error en lotsSummary: ' . $e->getMessage());
            
            return response()->json([
                'status' => 'error',
                'message' => 'Error al obtener resumen de lotes.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * ➕ Crear una nueva entrada
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // 🔍 Log de datos recibidos para debug
            Log::info('🔍 === INICIO DE REGISTRO DE ENTRADA ===');
            Log::info('🔍 Datos recibidos del frontend:', $request->all());
            Log::info('🔍 Usuario autenticado:', [
                'user_id' => Auth::id(),
                'user_name' => Auth::user()?->name
            ]);

            $validated = $request->validate([
                'product_id'   => 'required|integer|exists:products,id',
                'quantity'     => 'required|integer|min:1',
                'unit'         => 'nullable|string|max:50',
                'lot'          => 'nullable|string|max:50',
                'supplier_id'  => 'required|integer|exists:suppliers,id',
                'warehouse_id' => 'required|integer|exists:warehouses,id',
                'location_id'  => 'nullable|integer|exists:locations,id',
                'min_stock'    => 'required|integer|min:0',
            ]);

            Log::info('✅ Validación pasada correctamente');

            $user = Auth::user();
            if (!$user) {
                Log::error('❌ Usuario no autenticado intentando crear entrada');
                return response()->json([
                    'status'  => 'error',
                    'message' => 'Usuario no autenticado.',
                ], 401);
            }

            // Normalizar el lote
            $validated['lot'] = !empty($validated['lot']) ? strtoupper(trim($validated['lot'])) : 'SIN_LOTE';

            Log::info('📥 Datos validados y normalizados:', $validated);

            $entry = $this->entryService->createEntryWithInventoryAndUser($validated, $user->id);

            Log::info('✅ === ENTRADA REGISTRADA EXITOSAMENTE ===', [
                'entry_id' => $entry->id,
                'product_id' => $entry->product_id,
                'quantity' => $entry->quantity
            ]);

            return response()->json([
                'status'  => 'success',
                'message' => '✅ Entrada registrada correctamente.',
                'data'    => $entry,
            ], 201);

        } catch (ValidationException $e) {
            Log::warning('⚠️ Error de validación en store:', [
                'errors' => $e->errors(),
                'request_data' => $request->all()
            ]);
            
            return response()->json([
                'status'  => 'error',
                'message' => 'Error de validación.',
                'errors'  => $e->errors(),
            ], 422);
            
        } catch (Exception $e) {
            Log::error('❌ === ERROR AL REGISTRAR ENTRADA ===');
            Log::error('❌ Mensaje: ' . $e->getMessage());
            Log::error('❌ Archivo: ' . $e->getFile() . ' (Línea: ' . $e->getLine() . ')');
            Log::error('❌ Usuario: ' . Auth::id());
            Log::error('❌ Datos recibidos:', $request->all());
            Log::error('❌ Stack trace:', ['trace' => $e->getTraceAsString()]);

            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
                'error'   => config('app.debug') ? [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => explode("\n", $e->getTraceAsString())
                ] : null,
            ], 500);
        }
    }

    /**
     * 🔍 Mostrar una entrada
     */
    public function show(int $id): JsonResponse
    {
        try {
            $entry = $this->entryService->getEntryById($id);

            return response()->json([
                'status'  => 'success',
                'message' => 'Detalles de la entrada obtenidos correctamente.',
                'data'    => $entry,
            ]);
        } catch (Exception $e) {
            Log::error('❌ Error en show de entrada: ' . $e->getMessage());
            
            return response()->json([
                'status'  => 'error',
                'message' => 'Entrada no encontrada.',
            ], 404);
        }
    }

    /**
     * ✏️ Actualizar una entrada existente
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'quantity'     => 'sometimes|integer|min:1',
                'unit'         => 'sometimes|string|max:50',
                'lot'          => 'sometimes|string|max:50',
                'location_id'  => 'sometimes|integer|exists:locations,id',
                'min_stock'    => 'sometimes|integer|min:0',
            ]);

            $entry = $this->entryService->updateEntry($validated, $id);

            return response()->json([
                'status'  => 'success',
                'message' => 'Entrada actualizada exitosamente.',
                'data'    => $entry,
            ]);
            
        } catch (ValidationException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Error de validación.',
                'errors'  => $e->errors(),
            ], 422);
            
        } catch (Exception $e) {
            Log::error('❌ Error al actualizar entrada: ' . $e->getMessage());
            
            return response()->json([
                'status'  => 'error',
                'message' => 'Error al actualizar la entrada.',
            ], 500);
        }
    }

    /**
     * 🗑️ Eliminar una entrada
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->entryService->deleteEntry($id);

            return response()->json([
                'status'  => 'success',
                'message' => 'Entrada eliminada correctamente.',
            ]);
        } catch (Exception $e) {
            Log::error('❌ Error al eliminar entrada: ' . $e->getMessage());
            
            return response()->json([
                'status'  => 'error',
                'message' => 'Error al eliminar la entrada.',
            ], 500);
        }
    }

    /**
     * 📊 Resumen de entradas
     */
    public function summary(): JsonResponse
    {
        try {
            $summary = $this->entryService->getSummary();

            return response()->json([
                'status'  => 'success',
                'message' => 'Resumen de entradas obtenido correctamente.',
                'data'    => $summary,
            ]);
        } catch (Exception $e) {
            Log::error('❌ Error en summary: ' . $e->getMessage());
            
            return response()->json([
                'status'  => 'error',
                'message' => 'Error al obtener el resumen.',
            ], 500);
        }
    }

    /**
     * 📦 Datos para selects de formulario
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