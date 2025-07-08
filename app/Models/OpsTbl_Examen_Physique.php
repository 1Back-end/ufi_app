<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class OpsTbl_Examen_Physique extends Model
{
    // Table associée
    protected $table = 'ops_tbl__examen__physiques';

    // Champs remplissables (à adapter selon besoin)
    protected $fillable = [
        'code',
        'libelle',
        'resultat',
        'dossier_consultation_id',
        'categorie_examen_physique_id',
        'is_deleted',
        'created_by',
        'updated_by',
    ];


    public function categorieExamenPhysique()
    {
        return $this->belongsTo(ConfigTblCategoriesExamenPhysique::class, 'categorie_examen_physique_id');
    }

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
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($examenPhysique) {
            $prefix = 'EXAMEN-';
            $timestamp = now()->format('ymdHi');

            $random = strtoupper(Str::random(7));
            $examenPhysique->code = $prefix . $timestamp . $random;
        });
    }
    //
}
