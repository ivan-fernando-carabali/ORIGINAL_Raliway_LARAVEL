<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'abbr'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación con productos
     */
    public function products()
    {
        return $this->hasMany(Product::class, 'unit_id');
    }

    /**
     * Accessor para obtener el nombre completo con abreviación
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->name} ({$this->abbr})";
    }
}