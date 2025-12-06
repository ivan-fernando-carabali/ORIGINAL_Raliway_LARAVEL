<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Inventory;
use Illuminate\Support\Facades\DB;

class ProductService
{
    public function getAll($filters = [])
{
    $query = Product::with(['categoria', 'inventory', 'suppliers'])
        ->search($filters['search'] ?? null)
        ->filterByCategory($filters['category_id'] ?? null)
        ->status($filters['status'] ?? null);

    if (isset($filters['min_price']) && isset($filters['max_price'])) {
        $query->priceRange($filters['min_price'], $filters['max_price']);
    }

    return $query->paginate($filters['perPage'] ?? 100);
}


    /**
     * Crear producto con inventario automático
     */
    public function create(array $data): Product
    {
        return DB::transaction(function () use ($data) {

            // 🔹 1. Crear producto
            $product = Product::create([
                'name' => $data['name'],
                'category_id' => $data['category_id'],
                'reference' => $data['reference'],
                'unit_measurement' => $data['unit_measurement'],
                'batch' => $data['batch'],
                'expiration_date' => $data['expiration_date'] ?? null,
                'image' => $data['image'] ?? null,
            ]);

            // 🔹 2. Crear inventario asociado al producto
            Inventory::create([
                'product_id' => $product->id,
                'quantity' => $data['cantidad'] ?? 0,
                'min_stock' => $data['min_stock'] ?? 0,
                'location' => $data['location'] ?? 'Sin ubicación',
            ]);

            return $product->load('inventory');
        });
    }

    public function update(Product $product, array $data): Product
    {
        $product->update($data);
        return $product;
    }


    public function list(array $filters = [])
    {
        $search = $filters['search'] ?? null;
        $perPage = (int)($filters['perPage'] ?? 100);

        $query = Product::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                  ->orWhere('reference', 'like', "%$search%")
                  ->orWhere('batch', 'like', "%$search%");
            });
        }

        return $query->orderBy('name')->paginate($perPage);
    }


    public function delete(Product $product): bool
    {
        return $product->delete();
    }
}
