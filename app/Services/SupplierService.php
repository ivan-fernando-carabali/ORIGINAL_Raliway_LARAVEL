<?php

namespace App\Services;

use App\Models\Supplier;

class SupplierService
{
    public function list(array $filters = [])
    {
        $search = $filters['search'] ?? null;
        $perPage = (int)($filters['perPage'] ?? 100);

        $query = Supplier::query();
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                  ->orWhere('email', 'like', "%$search%")
                  ->orWhere('phone', 'like', "%$search%");
            });
        }

        return $query->orderBy('name')->paginate($perPage);
    }

    public function create(array $data): Supplier
    {
        return Supplier::create($data);
    }

    public function update(Supplier $supplier, array $data): Supplier
    {
        $supplier->update($data);
        return $supplier->fresh();
    }

    public function delete(Supplier $supplier): bool
    {
        return $supplier->delete();
    }
}


