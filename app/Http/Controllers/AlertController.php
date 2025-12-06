<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Services\AlertService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    public function __construct(
        protected AlertService $alertService
    ) {}

    /**
     * 📋 Listar alertas con filtros opcionales
     */
   public function index(Request $request): JsonResponse
{
    $validated = $request->validate([
        'alert_type' => 'nullable|in:bajo_stock,sin_stock',
        'status' => 'nullable|in:activa,pendiente,resuelta,orden_enviada',
        'product_id' => 'nullable|integer|exists:products,id',
        'date_from' => 'nullable|date',
        'date_to' => 'nullable|date|after_or_equal:date_from',
    ], [
        'alert_type.in' => 'El tipo de alerta debe ser: bajo_stock o sin_stock',
        'status.in' => 'El estado debe ser: activa, pendiente, resuelta u orden_enviada',
        'product_id.exists' => 'El producto especificado no existe',
    ]);

    // Traducir los filtros internos
    if (!empty($validated['alert_type'])) {
        $validated['alert_type'] = match ($validated['alert_type']) {
            'bajo_stock' => Alert::TYPE_LOW_STOCK,
            'sin_stock'  => Alert::TYPE_OUT_OF_STOCK,
            default      => $validated['alert_type'],
        };
    }

    if (!empty($validated['status'])) {
        $validated['status'] = match ($validated['status']) {
            'activa' => Alert::STATUS_NEW,
            'pendiente' => Alert::STATUS_ACTIVE,
            'resuelta'  => Alert::STATUS_RESOLVED,
            'orden_enviada' => Alert::STATUS_ORDER_SENT,
            default     => $validated['status'],
        };
    }

    // Obtener alertas y mapear para incluir lote y referencia
    $alerts = $this->alertService->getAlerts($validated)->map(function($alert) {
        // Mapear el status del modelo al frontend
        $status = match($alert->status) {
            Alert::STATUS_NEW => 'activa',
            Alert::STATUS_ACTIVE => 'pendiente',
            Alert::STATUS_RESOLVED => 'resuelta',
            Alert::STATUS_ORDER_SENT => 'orden_enviada',
            default => $alert->status,
        };
        
        return [
            'id' => $alert->id,
            'product_id' => $alert->product_id,
            'inventory_id' => $alert->inventory_id,
            'message' => $alert->message,
            'alert_type' => $alert->alert_type,
            'status' => $status, // Mapeado correctamente para el frontend
            'date' => $alert->date,
            'resolved_at' => $alert->resolved_at,
            'product' => [
                'id' => $alert->product->id ?? null,
                'name' => $alert->product->name ?? 'Producto desconocido',
                'lot' => $alert->inventory->lot ?? $alert->product->batch ?? null,
                'batch' => $alert->inventory->lot ?? $alert->product->batch ?? null,
                'reference' => $alert->product->reference ?? null,
                'codigo_de_barras' => $alert->product->codigo_de_barras ?? null,
                'suppliers' => $alert->product->suppliers ?? [],
                'supplier' => $alert->product->suppliers->first() ?? null,
            ],
            'inventory' => $alert->inventory ? [
                'id' => $alert->inventory->id ?? null,
                'stock' => $alert->inventory->stock ?? $alert->inventory->stock_actual ?? 0,
                'stock_actual' => $alert->inventory->stock_actual ?? $alert->inventory->stock ?? 0,
                'min_stock' => $alert->inventory->min_stock ?? 0,
            ] : null,
        ];
    });

    return response()->json([
        'status' => 'success',
        'message' => 'Listado de alertas obtenido correctamente',
        'data' => $alerts,
        'total' => $alerts->count(),
    ], 200);
}


    /**
     * 🔍 Mostrar una alerta específica
     */
    public function show(int $id): JsonResponse
    {
        try {
            $alert = Alert::with(['product', 'inventory'])->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => $alert,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Alerta no encontrada',
            ], 404);
        }
    }

    /**
     * ✅ Resolver una alerta manualmente
     */
    public function resolve(int $id): JsonResponse
    {
        try {
            $alert = $this->alertService->resolveAlert($id);

            return response()->json([
                'status' => 'success',
                'message' => 'Alerta resuelta correctamente',
                'data' => $alert,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Alerta no encontrada',
            ], 404);
        }
    }

    /**
     * 🔄 Actualizar el estado de una alerta (pendiente, resuelta u orden_enviada)
     */
    public function updateStatus(Request $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validate([
                'status' => 'required|in:activa,pendiente,resuelta,orden_enviada',
            ], [
                'status.required' => 'El estado es requerido',
                'status.in' => 'El estado debe ser: activa, pendiente, resuelta u orden_enviada',
            ]);

            $alert = \App\Models\Alert::findOrFail($id);

            // Mapear el estado del frontend al del modelo
            $status = match($validated['status']) {
                'activa' => \App\Models\Alert::STATUS_NEW,
                'pendiente' => \App\Models\Alert::STATUS_ACTIVE,
                'resuelta' => \App\Models\Alert::STATUS_RESOLVED,
                'orden_enviada' => \App\Models\Alert::STATUS_ORDER_SENT,
                default => $validated['status'],
            };

            $updateData = [
                'status' => $status,
            ];

            // Si se marca como resuelta, agregar fecha de resolución
            if ($status === \App\Models\Alert::STATUS_RESOLVED && !$alert->resolved_at) {
                $updateData['resolved_at'] = now();
            }

            // Si se marca como activa, pendiente o orden_enviada, limpiar fecha de resolución
            if ($status === \App\Models\Alert::STATUS_NEW || $status === \App\Models\Alert::STATUS_ACTIVE || $status === \App\Models\Alert::STATUS_ORDER_SENT) {
                $updateData['resolved_at'] = null;
            }

            $alert->update($updateData);
            $alert->refresh();

            return response()->json([
                'status' => 'success',
                'message' => 'Estado de alerta actualizado correctamente',
                'data' => $alert->load(['product', 'inventory']),
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error de validación',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Alerta no encontrada',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al actualizar el estado de la alerta',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * 📊 Obtener estadísticas de alertas
     */
    public function stats(): JsonResponse
    {
        $stats = $this->alertService->getStats();

        return response()->json([
            'status' => 'success',
            'data' => $stats,
        ], 200);
    }

    /**
     * 🔄 Verificar todo el inventario y actualizar alertas
     */
    public function checkAll(): JsonResponse
    {
        try {
            $this->alertService->checkAllInventory();

            return response()->json([
                'status' => 'success',
                'message' => 'Inventario verificado y alertas actualizadas correctamente',
                'stats' => $this->alertService->getStats(),
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al verificar el inventario',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * ℹ️ Obtener opciones válidas para filtros
     */
    public function filterOptions(): JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'alert_types' => [
                    [
                        'value' => Alert::TYPE_LOW_STOCK,
                        'label' => '📉 Stock Bajo'
                    ],
                    [
                        'value' => Alert::TYPE_OUT_OF_STOCK,
                        'label' => '🚫 Sin Stock'
                    ],
                ],
                'statuses' => [
                    [
                        'value' => Alert::STATUS_NEW,
                        'label' => 'Activa'
                    ],
                    [
                        'value' => Alert::STATUS_ACTIVE,
                        'label' => 'Orden Enviada'
                    ],
                    [
                        'value' => Alert::STATUS_RESOLVED,
                        'label' => 'Resuelta'
                    ],
                    [
                        'value' => Alert::STATUS_ORDER_SENT,
                        'label' => 'Orden Enviada'
                    ],
                ]
            ]
        ], 200);
    }
}
