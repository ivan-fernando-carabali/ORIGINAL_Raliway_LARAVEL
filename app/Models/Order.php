<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory;

    // ==================================================
    // 🧱 CAMPOS ASIGNABLES
    // ==================================================
    protected $fillable = [
        'user_id',
        'product_id',
        'supplier_id',
        'alert_id',
        'inventory_id',
        'dep_buy_id',
        'quantity',
        'supplier_email',
        'date',
        'state'
    ];

    // ==================================================
    // 🔄 CASTS
    // ==================================================
    protected $casts = [
        'date' => 'date',
        'quantity' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ==================================================
    // ⚙️ CONFIGURACIÓN DE LISTAS BLANCAS PARA FILTROS
    // ==================================================
    protected array $allowIncluded = ['supplier', 'user', 'product', 'inventory', 'alert', 'depBuy'];
    protected array $allowFilter   = ['id', 'state', 'supplier_id', 'product_id', 'user_id'];
    protected array $allowSort     = ['id', 'state', 'date', 'created_at'];

    // ==================================================
    // 🔗 RELACIONES
    // ==================================================
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function alert()
    {
        return $this->belongsTo(Alert::class);
    }

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    public function depBuy()
    {
        return $this->belongsTo(DepBuy::class);
    }

    // ==================================================
    // 🔎 SCOPES (INCLUSIÓN, FILTRO, ORDEN, PAGINACIÓN)
    // ==================================================
    public function scopeIncluded(Builder $query)
    {
        if (empty($this->allowIncluded) || empty(request('included'))) {
            return $query;
        }

        $relations = explode(',', request('included'));
        $allowIncluded = collect($this->allowIncluded);

        foreach ($relations as $key => $relationship) {
            if (!$allowIncluded->contains($relationship)) {
                unset($relations[$key]);
            }
        }

        if (!empty($relations)) {
            $query->with($relations);
        }

        return $query;
    }

    public function scopeFilter(Builder $query)
    {
        if (empty($this->allowFilter)) {
            return $query;
        }

        $filters = request('filter', []) + request()->only($this->allowFilter);

        foreach ($filters as $filter => $value) {
            if (in_array($filter, $this->allowFilter)) {
                if (is_numeric($value)) {
                    $query->where($filter, $value);
                } elseif (strtotime($value)) {
                    $query->whereDate($filter, $value);
                } else {
                    $query->where($filter, 'LIKE', '%' . $value . '%');
                }
            }
        }

        return $query;
    }

    public function scopeSort(Builder $query)
    {
        $sortFields = explode(',', request('sort', ''));
        $allowSort = collect($this->allowSort);

        foreach ($sortFields as $sortField) {
            $direction = 'asc';
            if (substr($sortField, 0, 1) === '-') {
                $direction = 'desc';
                $sortField = substr($sortField, 1);
            }

            if ($allowSort->contains($sortField)) {
                $query->orderBy($sortField, $direction);
            }
        }

        return $query;
    }

    public function scopeGetOrPaginate(Builder $query)
    {
        $perPage = intval(request('perPage', 0));
        return $perPage > 0 ? $query->paginate($perPage) : $query->get();
    }

    // ==================================================
    // 🧠 MÉTODOS AUXILIARES DE ESTADO
    // ==================================================
    public function isPending(): bool
    {
        return $this->state === 'pending';
    }

    public function isSent(): bool
    {
        return $this->state === 'sent';
    }

    public function isCompleted(): bool
    {
        return $this->state === 'completed';
    }

    public function isCancelled(): bool
    {
        return $this->state === 'cancelled';
    }

    public function markAsSent(): void
    {
        $this->update(['state' => 'sent']);
    }

    public function markAsCompleted(): void
    {
        $this->update(['state' => 'completed']);
    }

    public function cancel(): void
    {
        $this->update(['state' => 'cancelled']);
    }
}
