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
        'type_observation', // Nouveau champ ajouté
        'nbre_jours',
        'nbre_heures',       // Nouveau champ ajouté
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

    public function infirmieres()
    {
        return $this->belongsToMany(
            Nurse::class,
            'infirmiere_mise_observation',
            'mise_observation_id',
            'infirmiere_id'
        );
    }


    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    public function prescriptionPharmaceutique()
    {
        return $this->hasOne(PrescriptionPharmaceutique::class, 'mise_en_observation_id');
    }

    //
}
