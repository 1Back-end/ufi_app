<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmplacementProduit extends Model
{
    use SoftDeletes;

    protected $table = 'emplacement_produit';

    protected $fillable = [
        'id_produit',
        'id_emplacement',
        'created_by',
        'updated_by',
    ];


    public function produit()
    {
        return $this->belongsTo(\App\Models\Product::class, 'id_produit');
    }

    public function emplacement()
    {
        return $this->belongsTo(\App\Models\EmplacementsProduct::class, 'id_emplacement');
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
    }
}
