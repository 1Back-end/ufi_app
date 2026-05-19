<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConsultantPrestationShare extends Model
{
    use SoftDeletes;

    protected $table = 'consultant_prestation_shares';

    protected $fillable = [
        'consultant_id',
        'prestation_type_id',
        'share_rate',
        'calculation_type',
        'is_active',
        'created_by',
        'updated_by',
        'price'
    ];


    public function consultant()
    {
        return $this->belongsTo(Consultant::class);
    }

    public function prestationType()
    {
        return $this->belongsTo(PrestationCategory::class, 'prestation_type_id');
    }

    // Créateur
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Modificateur
    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
