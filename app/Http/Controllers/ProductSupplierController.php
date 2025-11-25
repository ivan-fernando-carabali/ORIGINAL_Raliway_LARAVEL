<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Product;
use App\Models\Supplier;

class ProductSupplierController extends Controller
{
    /**
     * 📦 Asocia múltiples productos a un proveedor.
     * Espera en el body:
     * {
     *   "products": [
     *      { "product_id": 1, "unit_cost": 5000, "batch": "LoteX" },
     *      { "product_id": 2, "unit_cost": 7500 }
     *   ]
     * }
     */
    public function attachProductsToSupplier(Request $request, $supplierId)
    {
        $request->validate([
            'products' => 'required|array|min:1',
            'products.*.product_id' => 'required|integer|exists:products,id',
            'products.*.unit_cost' => 'nullable|numeric|min:0',
            'products.*.batch' => 'nullable|string|max:255',
        ]);

        // Validar límite de 50 productos por proveedor
        $MAX_PRODUCTOS = 50;
        $supplier = Supplier::findOrFail($supplierId);
        $productosActuales = $supplier->products()->count();
        $productosNuevos = count($request->products);
        
        // Filtrar productos que ya están asociados
        $productosIds = collect($request->products)->pluck('product_id')->unique();
        $productosYaAsociados = $supplier->products()
            ->whereIn('products.id', $productosIds)
            ->count();
        
        $productosRealmenteNuevos = $productosNuevos - $productosYaAsociados;
        $totalDespues = $productosActuales + $productosRealmenteNuevos;

        if ($totalDespues > $MAX_PRODUCTOS) {
            $disponibles = $MAX_PRODUCTOS - $productosActuales;
            return response()->json([
                'status' => 'error',
                'message' => "No se pueden asociar {$productosRealmenteNuevos} productos. Solo se pueden agregar {$disponibles} productos más (máximo {$MAX_PRODUCTOS} por proveedor).",
                'current_count' => $productosActuales,
                'max_allowed' => $MAX_PRODUCTOS,
                'available_slots' => max(0, $disponibles)
            ], 422);
        }

        DB::beginTransaction();

        try {
            foreach ($request->products as $productData) {
                $this->attachProductToSupplier((int) $supplierId, $productData);
            }

            DB::commit();

            $supplier = Supplier::with('products')->findOrFail($supplierId);

            return response()->json([
                'status'  => 'success',
                'message' => '✅ Productos asociados o actualizados correctamente',
                'data'    => $supplier->products,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('❌ Error al asociar productos a proveedor: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Ocurrió un error al asociar productos',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * 🔹 Asocia o actualiza un producto específico con un proveedor.
     * - Si ya existe en la tabla pivote, se actualiza.
     * - Si no existe, se crea la asociación.
     */
    protected function attachProductToSupplier(int $supplierId, array $data): void
    {
        // 1️⃣ Buscar proveedor y producto
        $supplier = Supplier::findOrFail($supplierId);
        $product  = Product::findOrFail($data['product_id']);

        // 2️⃣ Comprobar si ya existe la relación
        $exists = $supplier->products()
            ->where('products.id', $product->id)
            ->exists();

        // 3️⃣ Preparar datos del pivot (priorizando datos del producto catálogo)
        $pivotData = [
            'unit_cost'          => $data['unit_cost'] ?? null,
            'supplier_reference' => $product->reference, // ✅ viene del catálogo
            'batch'              => $data['batch'] ?? $product->batch,
        ];

        // 4️⃣ Actualizar o crear asociación
        if ($exists) {
            $supplier->products()->updateExistingPivot($product->id, $pivotData);
        } else {
            $supplier->products()->attach($product->id, $pivotData);
        }
    }

    /**
     * 🔍 Obtiene todos los productos asociados a un proveedor.
     */
    public function getProductsBySupplier($supplierId)
    {
        $supplier = Supplier::with(['products' => function ($q) {
            $q->select('products.id', 'products.name', 'products.reference', 'products.batch', 'products.image')
              ->withPivot('unit_cost', 'supplier_reference', 'batch', 'created_at', 'updated_at');
        }])->findOrFail($supplierId);

        return response()->json([
            'status'  => 'success',
            'data'    => $supplier->products,
        ]);
    }

    /**
     * ❌ Elimina la relación de un producto con un proveedor.
     */
    public function detachProductFromSupplier($supplierId, $productId)
    {
        $supplier = Supplier::findOrFail($supplierId);

        if ($supplier->products()->where('products.id', $productId)->exists()) {
            $supplier->products()->detach($productId);

            return response()->json([
                'status'  => 'success',
                'message' => '🗑️ Producto desvinculado correctamente del proveedor',
            ]);
        }

        return response()->json([
            'status'  => 'error',
            'message' => '⚠️ El producto no estaba asociado a este proveedor',
        ], 404);
    }
}
