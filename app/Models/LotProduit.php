<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LotProduit extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'lot_produits';

    protected $primaryKey = 'id';

    protected $fillable = [
        'numero_lot_fabricant',
        'date_peremption',
        'date_reception',
        'quantite_actuelle',
        'statut',
        'id_produit',
        'id_emplacement',
        'created_by',
        'updated_by',
    ];


    public function produit()
    {
        return $this->belongsTo(Product::class, 'id_produit');
    }


    public function emplacement()
    {
        return $this->belongsTo(EmplacementsProduct::class, 'id_emplacement');
    }


    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
