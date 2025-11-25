<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use App\Models\ProductSupplier;

class Supplier extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'tax_id',
        'status',
    ];

    protected array $allowIncluded = ['products'];
    protected array $allowFilter   = ['id', 'name', 'email', 'phone', 'address'];
    protected array $allowSort     = ['id', 'name'];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_supplier', 'supplier_id', 'product_id')
                    ->using(ProductSupplier::class)
                    ->withPivot('unit_cost', 'supplier_reference', 'batch')
                    ->withTimestamps(); // importante si la tabla pivote tiene timestamps
    }

    // ... scopes (included, filter, sort, getOrPaginate) ...
}
