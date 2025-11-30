<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Entry extends Model
{
    /*
    |--------------------------------------------------------------------------
    | 🔒 Campos rellenables (mass assignment)
    |--------------------------------------------------------------------------
    |
    | Incluimos user_id porque el valor se asigna desde el backend
    | (Auth::user()->id) antes de crear el registro con create().
    |
    */
    protected $fillable = [
        'product_id',
        'quantity',           // Cantidad ingresada
        'unit',               // Unidad de medida
        'lot',                // Lote
        'supplier_id',
        'ubicacion_interna',  // Ubicación interna
        'min_stock',          // Stock mínimo
        'stock',              // Stock actual
        'user_id',            // Usuario autenticado que crea la entrada
    ];

    /*
    |--------------------------------------------------------------------------
    | ⚙️ Filtros y relaciones permitidas
    |--------------------------------------------------------------------------
    */
    protected array $allowIncluded = ['product', 'supplier'];
    protected array $allowFilter   = ['id', 'product_id', 'supplier_id', 'quantity'];
    protected array $allowSort     = ['id', 'quantity', 'product_id', 'created_at'];

    /*
    |--------------------------------------------------------------------------
    | 🔗 Relaciones
    |--------------------------------------------------------------------------
    */

    /**
     * 🔗 Relación con el producto
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * 🔗 Relación con el proveedor
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    /**
     * 🔗 Relación con el usuario que registró la entrada
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /*
    |--------------------------------------------------------------------------
    | 🔍 Scopes personalizados
    |--------------------------------------------------------------------------
    */

    /**
     * Incluye relaciones permitidas mediante ?included=
     *
     * Ejemplo: /entries?included=product,supplier
     */
    public function scopeIncluded(Builder $query): void
    {
        $included = request('included');
        if (empty($included)) return;

        $relations = explode(',', $included);
        $allowIncluded = collect($this->allowIncluded);

        $relations = array_filter($relations, fn($relation) => $allowIncluded->contains($relation));
        $query->with($relations);
    }

    /**
     * Filtra resultados mediante ?filter[campo]=valor
     *
     * Ejemplo: /entries?filter[product_id]=1
     */
    public function scopeFilter(Builder $query): void
    {
        $filters = request('filter');
        if (empty($filters)) return;

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

    /**
     * Ordena resultados mediante ?sort=campo o ?sort=-campo
     *
     * Ejemplo: /entries?sort=-quantity
     */
    public function scopeSort(Builder $query): void
    {
        $sort = request('sort');
        if (empty($sort)) return;

        $sortFields = explode(',', $sort);
        $allowSort  = collect($this->allowSort);

        foreach ($sortFields as $sortField) {
            $direction = 'asc';

            if (str_starts_with($sortField, '-')) {
                $direction = 'desc';
                $sortField = substr($sortField, 1);
            }

            if ($allowSort->contains($sortField)) {
                $query->orderBy($sortField, $direction);
            }
        }
    }

    /**
     * Devuelve paginación si existe ?perPage, de lo contrario todos los registros
     *
     * Ejemplo: /entries?perPage=10
     */
    public function scopeGetOrPaginate(Builder $query)
    {
        $perPage = request('perPage');

        if ($perPage && intval($perPage) > 0) {
            return $query->paginate(intval($perPage));
        }

        return $query->get();
    }
}
