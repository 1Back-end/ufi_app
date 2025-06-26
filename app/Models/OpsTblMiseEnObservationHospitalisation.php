<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OpsTblMiseEnObservationHospitalisation extends Model
{
    use HasFactory;
    protected $table = 'ops_tbl_mise_en_observation_hospitalisation';

    protected $fillable = [
        'observation',
        'resume',
        'nbre_jours',
        'rapport_consultation_id',
        'infirmiere_id',
        'created_by',
        'updated_by',
        'is_deleted',
    ];


    public function rapportConsultation()
    {
        return $this->belongsTo(OpsTblRapportConsultation::class, 'rapport_consultation_id');
    }

    public function infirmiere()
    {
        return $this->belongsTo(Nurse::class, 'infirmiere_id');
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
