<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InventaireItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'inventaire_items';

    protected $fillable = [
        'inventaire_id',
        'product_id',
        'lot_id',
        'emplacement_id',
        'quantity_in_stock',
        'quantity_observed',
        'ecart',
        'created_by',
        'updated_by',
    ];

    // Relations
    public function inventaire()
    {
        return $this->belongsTo(Inventaire::class, 'inventaire_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function emplacement()
    {
        return $this->belongsTo(EmplacementsProduct::class, 'emplacement_id');
    }
    public function lot()
    {
        return $this->belongsTo(LotProduit::class, 'lot_id');
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
