<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\Product;
use App\Services\SupplierService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SupplierController extends Controller
{
    public function __construct(private SupplierService $service) {}

    /**
     * 📄 Listar proveedores con búsqueda y paginación
     */
    public function index(Request $request)
    {
        $query = Supplier::query();

        // 🔍 Filtro de búsqueda
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('tax_id', 'like', "%{$search}%");
            });
        }

        // 📄 Paginación (por defecto 10)
        $perPage = $request->get('perPage', 10);
        $suppliers = $query->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'message' => 'Lista de proveedores obtenida correctamente',
            'data' => $suppliers->items(),
            'total' => $suppliers->total(),
        ]);
    }

    /**
     * ➕ Crear nuevo proveedor
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'    => 'required|string|max:255',
            'email'   => 'nullable|email|max:255',
            'phone'   => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'tax_id'  => 'nullable|string|max:50',
            'status'  => 'nullable|boolean',
        ]);

        try {
            $supplier = $this->service->create($validated);

            return response()->json([
                'status'  => 'success',
                'message' => 'Proveedor creado correctamente',
                'data'    => $supplier
            ], 201);
        } catch (\Throwable $e) {
            Log::error('❌ Error creando proveedor: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error interno al crear proveedor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 🔍 Mostrar un proveedor por ID
     */
    public function show($id)
    {
        $supplier = Supplier::with('products')->find($id);

        if (!$supplier) {
            return response()->json([
                'status' => 'error',
                'message' => 'Proveedor no encontrado'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $supplier
        ]);
    }

    /**
     * ✏️ Actualizar proveedor
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name'    => 'sometimes|required|string|max:255',
            'email'   => 'nullable|email|max:255',
            'phone'   => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'tax_id'  => 'nullable|string|max:50',
            'status'  => 'nullable|boolean',
        ]);

        try {
            $supplier = Supplier::findOrFail($id);
            $supplier->fill($validated);

            if ($supplier->isDirty()) {
                $supplier->save();
            }

            return response()->json([
                'status'  => 'success',
                'message' => 'Proveedor actualizado correctamente',
                'data'    => $supplier->fresh()
            ]);
        } catch (\Throwable $e) {
            Log::error('❌ Error actualizando proveedor: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error interno al actualizar proveedor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 🗑️ Eliminar proveedor
     */
    public function destroy($id)
    {
        try {
            $supplier = Supplier::findOrFail($id);
            $supplier->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Proveedor eliminado correctamente'
            ]);
        } catch (\Throwable $e) {
            Log::error('❌ Error eliminando proveedor: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Error interno al eliminar proveedor',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 📦 Obtener productos asociados a un proveedor
     */
    public function getProducts(Supplier $supplier)
    {
        $products = $supplier->products()
            ->select('products.id', 'products.name', 'products.reference', 'products.batch', 'products.image')
            ->withPivot('unit_cost', 'supplier_reference', 'batch')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $products
        ]);
    }

    /**
     * 🔗 Asociar productos (sin eliminar existentes)
     */
    public function attachProducts(Request $request, Supplier $supplier)
    {
        $data = $request->validate([
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|integer|exists:products,id',
            'products.*.unit_cost' => 'nullable|numeric',
            'products.*.supplier_reference' => 'nullable|string|max:255',
            'products.*.batch' => 'nullable|string|max:50',
        ]);

        // Validar límite de 50 productos por proveedor
        $MAX_PRODUCTOS = 50;
        $productosActuales = $supplier->products()->count();
        $productosNuevos = count($data['products']);
        
        // Filtrar productos que ya están asociados
        $productosIds = collect($data['products'])->pluck('product_id')->unique();
        $productosYaAsociados = $supplier->products()
            ->whereIn('products.id', $productosIds)
            ->count();
        
        $productosRealmenteNuevos = $productosNuevos - $productosYaAsociados;
        $totalDespues = $productosActuales + $productosRealmenteNuevos;

        if ($totalDespues > $MAX_PRODUCTOS) {
            $disponibles = $MAX_PRODUCTOS - $productosActuales;
            return response()->json([
                'message' => "No se pueden asociar {$productosRealmenteNuevos} productos. Solo se pueden agregar {$disponibles} productos más (máximo {$MAX_PRODUCTOS} por proveedor).",
                'current_count' => $productosActuales,
                'max_allowed' => $MAX_PRODUCTOS,
                'available_slots' => max(0, $disponibles)
            ], 422);
        }

        $attachData = [];

        try {
            DB::beginTransaction();

            foreach ($data['products'] as $p) {
                $product = Product::find($p['product_id']);

                if (!$product) {
                    DB::rollBack();
                    return response()->json(['message' => 'Producto no encontrado'], 422);
                }

                // Validar que los datos coincidan con el catálogo
                if (isset($p['supplier_reference']) && $p['supplier_reference'] !== $product->reference) {
                    DB::rollBack();
                    return response()->json([
                        'message' => "Referencia incorrecta para el producto {$product->id}.",
                        'expected_reference' => $product->reference,
                    ], 422);
                }

                if (isset($p['batch']) && $p['batch'] !== $product->batch) {
                    DB::rollBack();
                    return response()->json([
                        'message' => "Lote incorrecto para el producto {$product->id}.",
                        'expected_batch' => $product->batch,
                    ], 422);
                }

                $attachData[$product->id] = [
                    'unit_cost' => $p['unit_cost'] ?? null,
                    'supplier_reference' => $product->reference,
                    'batch' => $product->batch,
                ];
            }

            $supplier->products()->syncWithoutDetaching($attachData);
            DB::commit();

            $productos = $supplier->products()->withPivot('unit_cost', 'supplier_reference', 'batch')->get();

            return response()->json([
                'message' => 'Productos asociados correctamente',
                'data' => $productos
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('SupplierController::attachProducts error: '.$e->getMessage());
            return response()->json([
                'message' => 'Error interno al asociar productos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * 🔄 Sincronizar productos (elimina los no incluidos)
     */
    public function syncProducts(Request $request, Supplier $supplier)
    {
        $data = $request->validate([
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|integer|exists:products,id',
            'products.*.unit_cost' => 'nullable|numeric',
            'products.*.supplier_reference' => 'nullable|string|max:255',
            'products.*.batch' => 'nullable|string|max:50',
        ]);

        $syncData = [];

        try {
            DB::beginTransaction();

            foreach ($data['products'] as $p) {
                $product = Product::find($p['product_id']);

                if (!$product) {
                    DB::rollBack();
                    return response()->json(['message' => 'Producto no encontrado'], 422);
                }

                if (isset($p['supplier_reference']) && $p['supplier_reference'] !== $product->reference) {
                    DB::rollBack();
                    return response()->json([
                        'message' => "Referencia incorrecta para el producto {$product->id}.",
                        'expected_reference' => $product->reference,
                    ], 422);
                }

                if (isset($p['batch']) && $p['batch'] !== $product->batch) {
                    DB::rollBack();
                    return response()->json([
                        'message' => "Lote incorrecto para el producto {$product->id}.",
                        'expected_batch' => $product->batch,
                    ], 422);
                }

                $syncData[$product->id] = [
                    'unit_cost' => $p['unit_cost'] ?? null,
                    'supplier_reference' => $product->reference,
                    'batch' => $product->batch,
                ];
            }

            $supplier->products()->sync($syncData);
            DB::commit();

            return response()->json(['message' => 'Productos sincronizados correctamente'], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('SupplierController::syncProducts error: '.$e->getMessage());
            return response()->json(['message' => 'Error interno al sincronizar productos', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * ❌ Desvincular un producto de un proveedor
     */
    public function detachProduct(Supplier $supplier, $productId)
    {
        if (!$supplier->products()->where('products.id', $productId)->exists()) {
            return response()->json([
                'message' => 'El producto no está asociado a este proveedor'
            ], 404);
        }

        $supplier->products()->detach($productId);

        return response()->json([
            'message' => 'Producto desvinculado correctamente'
        ]);
    }
}
