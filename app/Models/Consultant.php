<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Consultant extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'code_hopi',
        'code_service_hopi',
        'code_specialite',
        'code_titre',
        'ref_consult',
        'nom_consult',
        'prenom_consult',
        'nomcomplet_consult', // Ajouté ici
        'tel_consult',
        'tel1_consult',
        'email_consul',
        'type_consult',
        'status_consult',
        'created_by',
        'updated_by',
        'TelWhatsApp',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function codeHopi()
    {
        return $this->belongsTo(Hopital::class, 'code_hopi');
    }

    public function codeServiceHopi()
    {
        return $this->belongsTo(Service_Hopital::class, 'code_service_hopi');
    }

    public function codeSpecialite()
    {
        return $this->belongsTo(Specialite::class, 'code_specialite');
    }

    public function codeTitre()
    {
        return $this->belongsTo(Titre::class, 'code_titre');
    }

    public function createByConsult()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updateByConsult()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
