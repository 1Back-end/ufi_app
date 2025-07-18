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
        'facturable',
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
    public function categories()
    {
        return $this->belongsToMany(Category::class, 'product_category');
    }
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
    public function fournisseurs()
    {
        return $this->belongsToMany(Fournisseurs::class, 'product_fournisseur', 'product_id', 'fournisseur_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    public function prescriptions()
    {
        return $this->belongsToMany(PrescriptionPharmaceutique::class, 'prescription_pharmaceutique_has_ops_tbl_products', 'product_id', 'prescription_pharmaceutique_id')
            ->withPivot('quantite')
            ->withTimestamps();
    }
    //
}
