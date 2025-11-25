<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ProductSupplier extends Pivot
{
    protected $table = 'product_supplier';

    // La tabla pivote no tiene timestamps en tu diseño, así que false.
    public $timestamps = false;

    protected $fillable = [
        'supplier_id',
        'product_id',
        'unit_cost',
        'supplier_reference',
        'batch',
    ];
}
