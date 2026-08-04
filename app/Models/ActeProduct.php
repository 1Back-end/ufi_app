<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ActeProduct extends Model
{
    use SoftDeletes;

    protected $table = 'acte_products';

    protected $fillable = [
        'acte_id',
        'product_id',
        'quantity',
        'created_by',
        'updated_by',
    ];

    public function acte()
    {
        return $this->belongsTo(Acte::class, 'acte_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
