<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class PrescriptionPharmaceutiqueProduct extends Model
{
    use HasFactory;

    protected $table = 'prescription_pharmaceutique_has_ops_tbl_products';

    protected $fillable = [
        'prescription_pharmaceutique_id',
        'product_id',
        'quantite',
    ];

    public function prescription()
    {
        return $this->belongsTo(PrescriptionPharmaceutique::class, 'prescription_pharmaceutique_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
    //
}
