<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Output extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'inventory_id',
        'quantity',
        'unit',
        'lot',
        'user_id',
        'motivo',
    ];

    protected $casts = [
        'quantity'   => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function product()  { return $this->belongsTo(Product::class); }
    public function inventory(){ return $this->belongsTo(Inventory::class); }
    public function user()     { return $this->belongsTo(User::class); }

    public function getCantidadFormateadaAttribute()
    {
        return "{$this->quantity} " . ($this->unit ?? '');
    }

    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }
}
