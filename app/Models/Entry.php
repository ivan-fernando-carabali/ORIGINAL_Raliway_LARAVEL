<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Entry extends Model
{
    protected $fillable = [
        'product_id',
        'quantity',
        'unit',
        'lot',
        'expiration_date',      // ✅ AGREGADO
        'supplier_id',
        'warehouse_id',
        'ubicacion_interna',    // ✅ CAMBIADO DE location_id
        'min_stock',
        'stock',
        'user_id',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'min_stock' => 'decimal:2',
        'stock' => 'decimal:2',
        'expiration_date' => 'date',  // ✅ AGREGADO
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected array $allowIncluded = ['product', 'supplier', 'warehouse', 'user'];
    protected array $allowFilter = ['id', 'product_id', 'supplier_id', 'warehouse_id', 'quantity', 'lot'];
    protected array $allowSort = ['id', 'quantity', 'product_id', 'created_at', 'updated_at'];

    // ============ RELACIONES ============

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // ============ SCOPES ============

    public function scopeIncluded(Builder $query): void
    {
        $included = request('included');
        if (!$included) return;

        $relations = array_filter(
            explode(',', $included),
            fn($r) => in_array(trim($r), $this->allowIncluded)
        );

        if (!empty($relations)) {
            $query->with($relations);
        }
    }

    public function scopeFilter(Builder $query): void
    {
        $filters = request('filter', []);

        foreach ($filters as $key => $value) {
            if (in_array($key, $this->allowFilter) && $value !== null && $value !== '') {
                $query->where($key, $value);
            }
        }
    }

    public function scopeSort(Builder $query): void
    {
        $sort = request('sort');
        if (!$sort) return;

        foreach (explode(',', $sort) as $field) {
            $direction = str_starts_with($field, '-') ? 'desc' : 'asc';
            $field = ltrim($field, '-');

            if (in_array($field, $this->allowSort)) {
                $query->orderBy($field, $direction);
            }
        }
    }

    public function scopeGetOrPaginate(Builder $query)
    {
        $perPage = request('perPage');

        if ($perPage && is_numeric($perPage) && $perPage > 0) {
            return $query->paginate(intval($perPage));
        }

        return $query->get();
    }

    // ============ MÉTODOS AUXILIARES ============

    public function scopeByProduct(Builder $query, int $productId): void
    {
        $query->where('product_id', $productId);
    }

    public function scopeByLot(Builder $query, string $lot): void
    {
        $query->where('lot', $lot);
    }

    public function scopeRecent(Builder $query, int $days = 30): void
    {
        $query->where('created_at', '>=', now()->subDays($days));
    }
}