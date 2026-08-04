<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ApprovisionnementConditionnement extends Model
{
    use SoftDeletes;

    protected $table = 'approvisionnement_conditionnements';

    protected $fillable = [
        'approvisionnement_id',
        'product_id',
        'product_dosage_id',
        'quantite',
        'price',
        'created_by',
        'updated_by',
    ];

    public function approvisionnement()
    {
        return $this->belongsTo(Approvisionnement::class, 'approvisionnement_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function productDosage()
    {
        return $this->belongsTo(ProductDosage::class, 'product_dosage_id');
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
