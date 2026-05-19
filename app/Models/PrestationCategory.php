<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PrestationCategory extends Model
{
    use SoftDeletes,HasFactory;

    protected $table = 'type_prestations';

    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = [
        'id',
        'name',
        'created_by',
        'updated_by',
    ];

    public function consultantShares()
    {
        return $this->hasMany(ConsultantPrestationShare::class, 'prestation_type_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    //
}
