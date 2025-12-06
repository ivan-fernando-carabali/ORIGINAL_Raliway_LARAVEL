<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\OutputService;

use App\Services\AlertService;
use App\Models\Output;
use App\Models\Inventory;
use App\Models\Entry;


class OutputController extends Controller
{
    protected OutputService $service;
    protected AlertService $alertService;

    public function __construct(OutputService $service, AlertService $alertService)
    {
        $this->service = $service;
        $this->alertService = $alertService;
    }

    /**
     * 📦 Listar todas las salidas (OPTIMIZADO)
     */

   public function index(Request $request)
{
    $query = Output::query();

    // Eager loading optimizado - solo campos necesarios
    $query->with(['product:id,name', 'inventory:id,stock', 'user:id,name,lastname']);

    // Filtro por fecha desde
    if ($request->has('date_from') && $request->date_from) {
        $query->whereDate('created_at', '>=', $request->date_from);
    }

    // Filtro por fecha hasta
    if ($request->has('date_to') && $request->date_to) {
        $query->whereDate('created_at', '<=', $request->date_to);
    }

    // Filtro por producto
    if ($request->has('product_id') && $request->product_id) {
        $query->where('product_id', $request->product_id);
    }

    // Aplicar límite si se especifica
    if ($request->has('limit') && is_numeric($request->limit) && $request->limit > 0) {
        $query->limit((int) $request->limit);
    }

    // Ordenar
    $orderBy = $request->get('order_by', 'created_at');
    $order = $request->get('order', 'desc');
    if (in_array($orderBy, ['created_at', 'updated_at', 'quantity'])) {
        $query->orderBy($orderBy, $order === 'asc' ? 'asc' : 'desc');
    } else {
        $query->orderByDesc('created_at');
    }

    $outputs = $query->get();

    // Formatear salidas para incluir toda la información necesaria
    $outputsFormatted = $outputs->map(function ($output) {
        return [
            'id' => $output->id,
            'product_id' => $output->product_id,
            'product' => $output->product,
            'inventory_id' => $output->inventory_id,
            'inventory' => $output->inventory,
            'quantity' => $output->quantity,
            'cantidad' => $output->quantity,
            'unit' => $output->unit,
            'lot' => $output->lot,
            'batch' => $output->lot,
            'motivo' => $output->motivo,
            'created_at' => $output->created_at?->toDateTimeString(),
            'date' => $output->created_at?->toDateTimeString(),
            'fecha' => $output->created_at?->format('d/m/Y H:i'),
            'user' => $output->user ? [
                'id' => $output->user->id,
                'name' => $output->user->name,
                'lastname' => $output->user->lastname ?? '',
            ] : null,
            'user_id' => $output->user_id,
        ];
    });


    return response()->json([
        'status' => 'success',
        'message' => 'Lista de salidas obtenida correctamente.',

        'data' => $outputsFormatted

    ]);
}


    /**
     * ➕ Crear nueva salida
     */
 public function store(Request $request)
{
    $validated = $request->validate([
        'inventory_id' => 'required|exists:inventories,id',
        'quantity'     => 'required|numeric|min:1',
        'unit'         => 'nullable|string|max:20',
        'lot'          => 'nullable|string|max:50',
        'motivo'       => 'nullable|string|max:255',
    ]);

    $inventory = Inventory::with('product')->find($validated['inventory_id']);

    if (!$inventory) {
        return response()->json([
            'status' => 'error',
            'message' => 'Inventario no encontrado.'
        ], 404);
    }


    // 🔍 Validar que el lote existe en las entradas
    $loteIngresado = $validated['lot'] ?? null;
    if ($loteIngresado) {
        $loteIngresado = trim(strtoupper($loteIngresado));

        // Verificar si existe una entrada con este lote para este producto
        $entradaExiste = Entry::where('product_id', $inventory->product_id)
            ->whereRaw('UPPER(TRIM(lot)) = ?', [$loteIngresado])
            ->exists();

        if (!$entradaExiste) {
            // Obtener lotes disponibles para el mensaje de error
            $lotesDisponibles = Entry::where('product_id', $inventory->product_id)
                ->distinct()
                ->pluck('lot')
                ->map(function($lot) {
                    return strtoupper(trim($lot));
                })
                ->unique()
                ->values()
                ->toArray();

            return response()->json([
                'status' => 'error',
                'message' => "El lote '{$loteIngresado}' no existe en las entradas. Lotes disponibles: " . (count($lotesDisponibles) > 0 ? implode(', ', $lotesDisponibles) : 'Ninguno'),
                'lotes_disponibles' => $lotesDisponibles
            ], 422);
        }
    }


    // 🔍 Verificar stock disponible
    if ($inventory->stock < $validated['quantity']) {
        return response()->json([
            'status' => 'error',
            'message' => 'La cantidad solicitada excede el stock disponible.'
        ], 422);
    }

    // 🔹 Crear salida con todos los campos requeridos
    $output = Output::create([
        'product_id'   => $inventory->product_id, // 👈 importante
        'inventory_id' => $inventory->id,
        'quantity'     => $validated['quantity'],
        'unit'         => $validated['unit'] ?? $inventory->unit,
        'lot'          => $validated['lot'] ?? $inventory->lot,
        'motivo'       => $validated['motivo'],
        'user_id'      => Auth::id(), // 👈 registra el usuario
    ]);

    // 🔹 Descontar del inventario
    $inventory->stock -= $validated['quantity'];
    $inventory->save();


    // 🔔 Verificar stock y crear/actualizar alertas como PENDIENTE
    // Si el stock queda bajo o sin stock, la alerta debe estar pendiente
    $this->alertService->checkStock($inventory->fresh());


    return response()->json([
        'status' => 'success',
        'message' => '✅ Salida registrada correctamente.',
        'data' => $output->load(['product', 'inventory', 'user']),
    ], 201);
}


    /**
     * 📄 Mostrar detalles
     */
    public function show($id)
    {
        if (!is_numeric($id)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'El identificador de salida no es válido.',
            ], 400);
        }

        $data = $this->service->find((int) $id);

        return response()->json([
            'status'  => $data['error'] ? 'error' : 'success',
            'message' => $data['message'] ?? 'Detalles de la salida',
            'data'    => $data['data'] ?? null,
        ], $data['error'] ? 404 : 200);
    }

    /**
     * ✏️ Actualizar salida
     */
    public function update(Request $request, $id)
    {
        if (!is_numeric($id)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'El identificador de salida no es válido.',
            ], 400);
        }

        $validated = $request->validate([
            'inventory_id' => 'sometimes|exists:inventories,id',
            'quantity'     => 'sometimes|numeric|min:1',
            'unit'         => 'sometimes|string|max:20',
            'lot'          => 'sometimes|string|max:50',
            'motivo'       => 'sometimes|string|max:255',
        ]);

        $validated['user_id'] = Auth::id();

        $result = $this->service->update((int) $id, $validated);

        return response()->json([
            'status'  => $result['error'] ? 'error' : 'success',
            'message' => $result['message'],
            'data'    => $result['data'] ?? null,
        ], $result['error'] ? 422 : 200);
    }

    /**
     * 🗑️ Eliminar salida
     */
    public function destroy($id)
    {
        if (!is_numeric($id)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'El identificador de salida no es válido.',
            ], 400);
        }

        $result = $this->service->delete((int) $id);

        return response()->json([
            'status'  => $result['error'] ? 'error' : 'success',
            'message' => $result['message'],
        ], $result['error'] ? 422 : 200);
    }

    /**
     * 📊 Resumen
     */
    public function summary()
    {
        return response()->json([
            'status'  => 'success',
            'message' => 'Resumen de salidas',
            'data'    => $this->service->summary(),
        ]);
    }

    /**
     * 📋 Datos para formulario
     */
    public function formData()
    {
        return response()->json([
            'status'  => 'success',
            'message' => 'Datos de formulario',
            'data'    => $this->service->formData(),
        ]);
    }
}
