<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
class InventoryDetail extends Model
{
    protected $fillable = [
        'inventory_id',
        'product_id',
        'location_id',
        'current_stock',
        'min_threshold',
    ];

    public function inventory() {
        return $this->belongsTo(Inventory::class);
    }

    public function product() {
        return $this->belongsTo(Product::class);
    }

    public function location() {
        return $this->belongsTo(Location::class);
    }

    // Método para saber si está en alerta
    public function isLowStock() {
        return $this->current_stock < $this->min_threshold;
    }
}
