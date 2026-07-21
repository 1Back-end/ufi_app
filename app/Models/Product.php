<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'ref',
        'name',
        'barcode',
        'product_type_id',
        'generic_name',
        'manufacturer_reference',
        'product_type',
        'dosage',
        'laboratory_family',
        'storage_unit',
        'consumption_unit',
        'conversion_factor',
        'alert_threshold',
        'minimum_threshold',
        'storage_temperature',
        'purchase_price',
        'price',
        'pharmacy_price',
        'facturable',
        'fournisseurs_id',
        'Dosage_defaut',
        'schema_administration',
        'created_by',
        'updated_by',
        'status',
        'is_active',
        'allow_negative_stock',
        'has_expiration_date',
        'has_moratorium',
        'moratorium_months',
        'is_suspended',
        'is_out_of_stock'
    ];

    protected $casts = [
        'facturable'           => 'boolean',
        'is_active'            => 'boolean',
        'allow_negative_stock' => 'boolean',
        'is_suspended'         => 'boolean',
        'is_out_of_stock'      => 'boolean',
        'has_expiration_date'  => 'boolean',
        'has_moratorium'       => 'boolean',
        'moratorium_months'    => 'integer',
        'purchase_price'       => 'integer',
        'price'                => 'integer',
        'pharmacy_price'       => 'integer',
    ];

    public function packagings(): BelongsToMany
    {
        return $this->belongsToMany(Packaging::class, 'product_packaging', 'product_id', 'packaging_product_id')
            ->using(ProductPackaging::class)
            ->withPivot('id', 'is_default', 'created_by', 'updated_by')
            ->withTimestamps()
            ->wherePivotNull('deleted_at');
    }

    public function fournisseurs(): BelongsToMany
    {
        return $this->belongsToMany(Fournisseurs::class, 'product_fournisseur', 'product_id', 'fournisseur_id');
    }

    public function productType(): BelongsTo
    {
        return $this->belongsTo(ProductType::class, 'product_type_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function prescriptions(): BelongsToMany
    {
        return $this->belongsToMany(PrescriptionPharmaceutique::class, 'prescription_pharmaceutique_has_ops_tbl_products', 'product_id', 'prescription_pharmaceutique_id')
            ->withPivot('quantite')
            ->withTimestamps();
    }

    public function lots(): HasOne
    {
        return $this->hasOne(LotProduit::class, 'id_produit');
    }

    public function emplacements(): HasMany
    {
        return $this->hasMany(EmplacementProduit::class, 'id_produit');
    }

    public function purchaseOrderItems(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class, 'product_id');
    }
}
