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

    /**
     * Atributos que se agregan automáticamente al serializar a JSON
     */
    protected $appends = ['image_url'];

    /**
     * Obtener la URL completa de la imagen
     */
    public function getImageUrlAttribute()
    {
        if (!$this->image) {
            return null;
        }
        
        // Si la imagen ya es una URL completa, devolverla tal cual
        if (filter_var($this->image, FILTER_VALIDATE_URL)) {
            return $this->image;
        }
        
        // Obtener la URL base de la aplicación
        $baseUrl = rtrim(config('app.url'), '/');
        
        // Asegurar que la ruta de la imagen no tenga barras iniciales duplicadas
        $imagePath = ltrim($this->image, '/');
        
        // Construir la URL completa
        return $baseUrl . '/storage/' . $imagePath;
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
