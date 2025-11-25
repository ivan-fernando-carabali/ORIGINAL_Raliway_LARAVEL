<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EntryNote extends Model
{
     protected $fillable = ['product_id', 'entry_id', 'date', 'quantity', 'observation'];
    public function entry() { return $this->belongsTo(Entry::class); }
    public function product() { return $this->belongsTo(Product::class); }
}
