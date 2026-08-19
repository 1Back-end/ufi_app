<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LotProduitConditionnement extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'lot_produit_conditionnement';

    protected $primaryKey = 'id';

    protected $fillable = [
        'lot_produit_id',
        'product_packagings_id',
        'quantite',
        'price',
        'created_by',
        'updated_by',
        'pt'
    ];

    protected $casts = [
        'quantite' => 'decimal:2',
        'price' => 'decimal:2',
    ];

    public function lotProduit()
    {
        return $this->belongsTo(LotProduit::class, 'lot_produit_id');
    }

    public function packaging()
    {
        return $this->belongsTo(Packaging::class, 'product_packagings_id');
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
