<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Order extends Model
{
    protected $fillable = [
        'status', // Cambiado de 'state' a 'status' para coincidir con la BD
        'user_id', 
        'supplier_id',
        'product_id',
        'inventory_id',
        'alert_id',
        'quantity',
        'dep_buy_id',
        'supplier_email', // Agregado para almacenar el email del proveedor
        'date', // Campo requerido por la base de datos
        'notes',
        'sent_at',
        'received_at'
    ];

    protected $casts = [
        'date' => 'date',
        'sent_at' => 'datetime',
        'received_at' => 'datetime',
    ];

    // Relaciones permitidas en includes
    protected $allowIncluded = ['supplier', 'user', 'product', 'inventory', 'alert'];
    protected $allowFilter   = ['id', 'status'];
    protected $allowSort     = ['id', 'status', 'created_at'];

    // Relación con proveedor
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    // Relación con usuario
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relación con producto
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    // Relación con inventario
    public function inventory()
    {
        return $this->belongsTo(Inventory::class, 'inventory_id');
    }

    // Relación con alerta
    public function alert()
    {
        return $this->belongsTo(Alert::class, 'alert_id');
    }

    // Relación uno a muchos con productos (legacy)
    public function products()
    {
        return $this->hasMany(Product::class, 'order_id');
    }

    /* ----------------- SCOPES DINÁMICOS ----------------- */
    public function scopeIncluded(Builder $query)
    {
        if (empty($this->allowIncluded) || empty(request('included'))) {
            return;
        }

        $relations = explode(',', request('included'));
        $allowIncluded = collect($this->allowIncluded);

        foreach ($relations as $key => $relationship) {
            if (!$allowIncluded->contains($relationship)) {
                unset($relations[$key]);
            }
        }

        $query->with($relations);
    }

    public function scopeFilter(Builder $query)
    {
        if (empty($this->allowFilter) || empty(request('filter'))) {
            return;
        }

        $filters = request('filter');
        $allowFilter = collect($this->allowFilter);

        foreach ($filters as $filter => $value) {
            if ($allowFilter->contains($filter)) {
                if (is_numeric($value)) {
                    $query->where($filter, $value);
                } elseif (strtotime($value)) {
                    $query->whereDate($filter, $value);
                } else {
                    $query->where($filter, 'LIKE', '%' . $value . '%');
                }
            }
        }
    }

    public function scopeSort(Builder $query)
    {
        if (empty($this->allowSort) || empty(request('sort'))) {
            return;
        }

        $sortFields = explode(',', request('sort'));
        $allowSort = collect($this->allowSort);

        foreach ($sortFields as $sortField) {
            $direction = 'asc';
            if (substr($sortField, 0, 1) == '-') {
                $direction = 'desc';
                $sortField = substr($sortField, 1);
            }
            if ($allowSort->contains($sortField)) {
                $query->orderBy($sortField, $direction);
            }
        }
    }

    public function scopeGetOrPaginate(Builder $query)
    {
        if (request('perPage')) {
            $perPage = intval(request('perPage'));
            if ($perPage) {
                return $query->paginate($perPage);
            }
        }

        return $query->get();
    }
}
