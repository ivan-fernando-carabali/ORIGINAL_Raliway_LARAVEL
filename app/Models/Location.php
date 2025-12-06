<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Location extends Model
{
    // Nombre de la tabla (ajusta según tu migración)
    protected $table = 'Locations';

    // Campos permitidos en asignación masiva
    protected $fillable = ['row', 'level', 'capacity', 'warehouse_id'];

    // Listas blancas para querys dinámicas
    protected array $allowIncluded = ['warehouse'];
    protected array $allowFilter   = ['row', 'level'];
    protected array $allowSort     = ['row', 'level'];

    /* ================== RELACIONES ================== */
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class, 'warehouse_id');
    }

    public function inventories()
    {
        return $this->hasMany(Inventory::class, 'Location_id');
    }

    /* ================== SCOPES ================== */

    public function scopeIncluded(Builder $query)
    {
        if (empty(request('included'))) {
            return $query;
        }

        $relations = explode(',', request('included'));
        $relations = array_filter($relations, fn($rel) => collect($this->allowIncluded)->contains($rel));

        return $query->with($relations);
    }

    public function scopeFilter(Builder $query)
    {
        if (empty(request('filter'))) {
            return $query;
        }

        $filters = request('filter');
        foreach ($filters as $filter => $value) {
            if (collect($this->allowFilter)->contains($filter)) {
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
        if (empty(request('sort'))) {
            return $query;
        }

        $sortFields = explode(',', request('sort'));

        foreach ($sortFields as $sortField) {
            $direction = 'asc';
            if (str_starts_with($sortField, '-')) {
                $direction = 'desc';
                $sortField = substr($sortField, 1);
            }

            if (collect($this->allowSort)->contains($sortField)) {
                $query->orderBy($sortField, $direction);
            }
        }
        return $query;
    }

    public function scopeGetOrPaginate(Builder $query)
    {
        $perPage = intval(request('perPage'));
        return $perPage > 0 ? $query->paginate($perPage) : $query->get();
    }
}
