<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'ref',
        'name',
        'dosage',
        'voix_transmissions_id',
        'price',
        'unite_produits_id',
        'group_products_id',
        'categories_id',
        'unite_par_emballage',
        'condition_par_unite_emballage',
        'fournisseurs_id',
        'Dosage_defaut',
        'schema_administration',
        'created_by',
        'updated_by',
        'is_deleted',
        'status',
    ];

    // Relations
    public function voieTransmission()
    {
        return $this->belongsTo(VoixTransmissions::class, 'voix_transmissions_id');
    }

    public function uniteProduit()
    {
        return $this->belongsTo(UniteProduit::class, 'unite_produits_id');
    }

    public function groupProduct()
    {
        return $this->belongsTo(GroupProduct::class, 'group_products_id');
    }

    public function categorie()
    {
        return $this->belongsTo(Category::class, 'categories_id');
    }

    public function fournisseur()
    {
        return $this->belongsTo(Fournisseurs::class, 'fournisseurs_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    //
}
