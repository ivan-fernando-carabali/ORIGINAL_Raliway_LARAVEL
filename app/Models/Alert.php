<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Alert extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'inventory_id',
        'alert_type',
        'status',
        'message',
        'date',
        'resolved_at'
    ];

    protected $casts = [
        'date' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    // Constantes
    const TYPE_LOW_STOCK = 'bajo_stock';
    const TYPE_OUT_OF_STOCK = 'sin_stock';
    const STATUS_ACTIVE = 'pendiente';
    const STATUS_RESOLVED = 'resuelta';
    const STATUS_ORDER_SENT = 'orden_enviada';

    /* ----------------- RELACIONES ----------------- */

    /**
     * Relación con producto
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Relación con inventario
     */
    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    /**
     * 📦 NUEVA: Relación con órdenes (una alerta puede generar múltiples órdenes)
     */
    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    /* ----------------- SCOPES ----------------- */

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeResolved($query)
    {
        return $query->where('status', self::STATUS_RESOLVED);
    }

    public function scopeLowStock($query)
    {
        return $query->where('alert_type', self::TYPE_LOW_STOCK);
    }

    public function scopeOutOfStock($query)
    {
        return $query->where('alert_type', self::TYPE_OUT_OF_STOCK);
    }

    /* ----------------- HELPERS ----------------- */

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isResolved(): bool
    {
        return $this->status === self::STATUS_RESOLVED;
    }

    /**
     * 📦 NUEVO: Verificar si tiene órdenes asociadas
     */
    public function hasOrders(): bool
    {
        return $this->orders()->exists();
    }

    /* ----------------- ACCESSORS ----------------- */

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_ACTIVE => 'Pendiente',
            self::STATUS_RESOLVED => 'Resuelta',
            self::STATUS_ORDER_SENT => 'Orden Enviada',
            default => 'Desconocido'
        };
    }

    public function isOrderSent(): bool
    {
        return $this->status === self::STATUS_ORDER_SENT;
    }

    public function getTypeLabelAttribute(): string
    {
        return match($this->alert_type) {
            self::TYPE_LOW_STOCK => 'Stock Bajo',
            self::TYPE_OUT_OF_STOCK => 'Sin Stock',
            default => 'Desconocido'
        };
    }
}
