<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    /**
     * 🔒 Campos rellenables
     */
    protected $fillable = [
        'warehouse_id',
        'aisle',    // agregado de la nueva versión
        'row',
        'level',    // si necesitas 'level' también
        'capacity',
    ];

    /**
     * 🏷 Nombre de la tabla (opcional, si no sigue convención plural)
     */
    protected $table = 'locations';

    /**
     * ⚙️ Listas blancas para queries dinámicas
     */
    protected array $allowIncluded = ['warehouse'];
    protected array $allowFilter   = ['row', 'level', 'aisle'];
    protected array $allowSort     = ['row', 'level', 'aisle'];

    /* ================== RELACIONES ================== */

    /**
     * 🔗 Relación con el almacén
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    /**
     * 🔗 Relación con inventarios
     */
    public function inventories(): HasMany
    {
        return $this->hasMany(Inventory::class, 'location_id');
    }

    /**
     * 🔗 Relación con entradas (opcional)
     */
    public function entries(): HasMany
    {
        return $this->hasMany(Entry::class, 'location_id');
    }

    /* ================== SCOPES ================== */

    public function scopeIncluded(Builder $query): Builder
    {
        $relations = explode(',', request('included', ''));
        $relations = array_filter($relations, fn($rel) => in_array($rel, $this->allowIncluded));

        return $relations ? $query->with($relations) : $query;
    }

    public function scopeFilter(Builder $query): Builder
    {
        $filters = request('filter', []);
        foreach ($filters as $filter => $value) {
            if (!in_array($filter, $this->allowFilter)) continue;

            if (is_numeric($value)) {
                $query->where($filter, $value);
            } elseif (strtotime($value)) {
                $query->whereDate($filter, $value);
            } else {
                $query->where($filter, 'LIKE', "%$value%");
            }
        }
        return $query;
    }

    public function scopeSort(Builder $query): Builder
    {
        $sortFields = explode(',', request('sort', ''));

        foreach ($sortFields as $sortField) {
            $direction = 'asc';
            if (str_starts_with($sortField, '-')) {
                $direction = 'desc';
                $sortField = substr($sortField, 1);
            }

            if (in_array($sortField, $this->allowSort)) {
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
}
