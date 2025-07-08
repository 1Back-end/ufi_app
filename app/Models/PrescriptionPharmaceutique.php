<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PrescriptionPharmaceutique extends Model
{
    use HasFactory;

    protected $fillable = [
        'mise_en_observation_id',
        'created_by',
        'updated_by',
    ];

    public function miseEnObservation()
    {
        return $this->belongsTo(OpsTblMiseEnObservationHospitalisation::class, 'mise_en_observation_id');
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'prescription_pharmaceutique_has_ops_tbl_products', 'prescription_pharmaceutique_id', 'product_id')
            ->withPivot('quantite')
            ->withTimestamps();
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
