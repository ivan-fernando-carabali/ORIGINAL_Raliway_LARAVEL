<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Supplier;
use App\Models\ProductSupplier;

class Product extends Model
{
   protected $fillable = [
    'name',
    'category_id',
    'codigo_de_barras',
    'reference', // Mantener por compatibilidad
    'unit_measurement',
    'batch',
    'expiration_date',
    'icono1',
    'icono2',
    'icono3',
    'image',
];

    protected $casts = [
        'expiration_date' => 'date:Y-m-d',
    ];

    /*obtener imagen en product-supplier con el dominio */

    public function getImageUrlAttribute()
    {
        return $this->image ? url('storage/' . $this->image) : null;
    }



    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function categoria()
    {
        return $this->category();
    }

    public function detalles()
    {
        return $this->hasMany(ProductDetail::class);
    }

    public function inventory()
    {
        return $this->hasOne(Inventory::class, 'product_id');
    }

    // ... scopes ...

    // Relación muchos a muchos con Supplier. Usamos un pivot model.
    public function suppliers()
    {
        return $this->belongsToMany(Supplier::class, 'product_supplier', 'product_id', 'supplier_id')
                    ->using(ProductSupplier::class)
                    ->withPivot('unit_cost', 'supplier_reference', 'batch')
                    ->withTimestamps(); // importante si la tabla pivote tiene timestamps
    }
}
