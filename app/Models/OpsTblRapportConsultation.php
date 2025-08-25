<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class OpsTblRapportConsultation extends Model
{
    protected $fillable = [
        'code',
        'resume',
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
    public function ordonnance()
    {
        return $this->hasOne(Ordonnance::class, 'rapport_consultations_id', 'id');
    }
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($examenPhysique) {
            $prefix = 'RAPPORT-';
            $timestamp = now()->format('ymdHi');

            $random = strtoupper(Str::random(7));
            $examenPhysique->code = $prefix . $timestamp . $random;
        });
    }
    //
}
