<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Consultant extends Model
{
    use HasFactory;

    protected $fillable = [
        'code_hopi',
        'code_service_hopi',
        'code_specialite',
        'code_titre',
        'ref',
        'nom',
        'prenom',
        'nomcomplet', // Ajouté ici
        'tel',
        'tel1',
        'email',
        'type',
        'status',
        'created_by',
        'updated_by',
        'TelWhatsApp',
    ];



    public function code_hopi()
    {
        return $this->belongsTo(Hopital::class, 'code_hopi');
    }
    public function code_specialite()
    {
        return $this->belongsTo(Specialite::class, 'code_specialite');
    }
    public function code_titre()
    {
        return $this->belongsTo(Titre::class, 'code_titre');
    }

    public function code_service_hopi()
    {
        return $this->belongsTo(Service_Hopital::class, 'code_service_hopi');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
