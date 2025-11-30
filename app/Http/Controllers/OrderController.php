<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Alert;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * 📋 Listar todas las órdenes
     */
    public function index(Request $request)
    {
        try {
            $query = Order::with(['product', 'user', 'supplier', 'inventory', 'alert']);

            // Filtros opcionales
            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('user_id')) {
                $query->where('user_id', $request->user_id);
            }

            if ($request->has('supplier_id')) {
                $query->where('supplier_id', $request->supplier_id);
            }

            $orders = $query->orderBy('created_at', 'desc')->get();

            return response()->json([
                'status' => 'success',
                'data' => $orders
            ], 200);

        } catch (\Exception $e) {
            Log::error('❌ Error al obtener órdenes:', ['error' => $e->getMessage()]);
            return response()->json([
                'status' => 'error',
                'message' => 'Error al obtener las órdenes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 💾 Crear nueva orden
     */
    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            Log::info('📥 Datos recibidos para crear orden:', $request->all());

            $validator = Validator::make($request->all(), [
                'product_id' => 'required|integer|exists:products,id',
                'user_id' => 'required|integer|exists:users,id',
                'inventory_id' => 'required|integer|exists:inventories,id',
                'supplier_id' => 'required|integer|exists:suppliers,id',
                'quantity' => 'required|integer|min:1',
                'supplier_email' => 'required|email|max:255',
                'alert_id' => 'nullable|integer|exists:alerts,id',
            ]);

            if ($validator->fails()) {
                Log::error('❌ Validación fallida:', $validator->errors()->toArray());
                DB::rollBack();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $orderData = [
                'product_id' => (int)$request->product_id,
                'user_id' => (int)$request->user_id,
                'inventory_id' => (int)$request->inventory_id,
                'supplier_id' => (int)$request->supplier_id,
                'quantity' => (int)$request->quantity,
                'supplier_email' => $request->supplier_email,
                'status' => 'pending',
            ];

            if ($request->filled('alert_id')) {
                $orderData['alert_id'] = (int)$request->alert_id;
            }

            $order = Order::create($orderData);
            Log::info('✅ Orden creada con ID:', ['order_id' => $order->id]);

            if ($request->filled('alert_id')) {
                Alert::where('id', $request->alert_id)->update(['status' => 'resolved']);
                Log::info('✅ Alerta actualizada a resolved:', ['alert_id' => $request->alert_id]);
            }

            $order->load(['product', 'user', 'supplier', 'inventory', 'alert']);
            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Orden creada exitosamente',
                'data' => $order
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ Error al crear orden:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 'error',
                'message' => 'Error al crear la orden',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 🔍 Mostrar orden específica
     */
    public function show($id)
    {
        try {
            $order = Order::with(['product', 'user', 'supplier', 'inventory', 'alert'])
                ->findOrFail($id);

            return response()->json([
                'status' => 'success',
                'data' => $order
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Orden no encontrada',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * ✏️ Actualizar orden
     */
    public function update(Request $request, $id)
    {
        try {
            $order = Order::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'status' => 'sometimes|in:pending,approved,rejected,received',
                'quantity' => 'sometimes|integer|min:1',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $order->update($request->only(['status', 'quantity']));
            $order->load(['product', 'user', 'supplier', 'inventory', 'alert']);

            return response()->json([
                'status' => 'success',
                'message' => 'Orden actualizada exitosamente',
                'data' => $order
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error al actualizar orden:', ['error' => $e->getMessage()]);

            return response()->json([
                'status' => 'error',
                'message' => 'Error al actualizar la orden',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 🔄 Actualizar solo el estado
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $order = Order::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'status' => 'required|in:pending,approved,rejected,received',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $order->update(['status' => $request->status]);
            $order->load(['product', 'user', 'supplier', 'inventory', 'alert']);

            return response()->json([
                'status' => 'success',
                'message' => 'Estado actualizado exitosamente',
                'data' => $order
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al actualizar estado',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 🗑️ Eliminar orden
     */
    public function destroy($id)
    {
        try {
            $order = Order::findOrFail($id);
            $order->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Orden eliminada exitosamente'
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error al eliminar orden:', ['error' => $e->getMessage()]);

            return response()->json([
                'status' => 'error',
                'message' => 'Error al eliminar la orden',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 📊 Estadísticas de órdenes
     */
    public function statistics()
    {
        try {
            $stats = [
                'total' => Order::count(),
                'pending' => Order::where('status', 'pending')->count(),
                'approved' => Order::where('status', 'approved')->count(),
                'rejected' => Order::where('status', 'rejected')->count(),
                'received' => Order::where('status', 'received')->count(),
            ];

            return response()->json([
                'status' => 'success',
                'data' => $stats
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al obtener estadísticas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 🏭 Órdenes por proveedor
     */
    public function bySupplier($supplierId)
    {
        try {
            $orders = Order::with(['product', 'user', 'inventory', 'alert'])
                ->where('supplier_id', $supplierId)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $orders
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al obtener órdenes del proveedor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 📦 Órdenes por producto
     */
    public function byProduct($productId)
    {
        try {
            $orders = Order::with(['supplier', 'user', 'inventory', 'alert'])
                ->where('product_id', $productId)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $orders
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Error al obtener órdenes del producto',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
