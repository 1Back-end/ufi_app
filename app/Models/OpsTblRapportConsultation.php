<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OpsTblRapportConsultation extends Model
{
    protected $fillable = [
        'code',
        'conclusion',
        'recommandations',
        'dossier_consultation_id',
        'is_deleted',
        'created_by',
        'updated_by'
    ];

    public function dossierConsultation()
    {
        return $this->belongsTo(DossierConsultation::class, 'dossier_consultation_id');

    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    public function misesEnObservation()
    {
        return $this->hasMany(OpsTblMiseEnObservationHospitalisation::class, 'rapport_consultation_id');
    }
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($rapport) {
            $prefix = 'RAPPORT-';
            $timestamp = now()->format('YmdHis');
            $rapport->code = $prefix . $timestamp;
        });
    }
    //
}
