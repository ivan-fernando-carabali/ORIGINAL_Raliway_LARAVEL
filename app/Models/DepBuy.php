<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DepBuy extends Model
{
     protected $fillable =
['name', 'address', 'email', 'responsible', 'phone'];
    public function pedidos() { return $this->hasMany(Order::class); }
}
