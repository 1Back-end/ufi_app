<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OpsTbl_Motif_consultation extends Model
{
    use HasFactory;

    protected $table = 'ops_tbl__motif_consultations';

    protected $fillable = [
        'code',
        'description',
        'is_deleted',
        'libelle',
        'dossier_consultation_id',
        'categorie_visite_id',
        'type_visite_id',
        'created_by',
        'updated_by',
    ];

    // 🔗 Relation vers le dossier de consultation
    public function dossierConsultation()
    {
        return $this->belongsTo(DossierConsultation::class);
    }

    public function categorieVisite()
    {
        return $this->belongsTo(ConfigTblCategorieVisite::class);
    }

    function TypeVisite()
    {
        return $this->belongsTo(ConfigTblTypeVisite::class);

    }

    // 🔗 Créateur
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // 🔗 Modificateur
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    //
}
