<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class OpsTblEnquete extends Model
{
    protected $table = 'ops_tbl_enquetes';

    protected $fillable = [
        'code',
        'libelle',
        'resultat',
        'dossier_consultation_id',
        'categories_enquetes_id',
        'is_deleted',
        'created_by',
        'updated_by',
    ];


    public function dossierConsultation ()
    {
        return $this->belongsTo(DossierConsultation::class, 'dossier_consultation_id');

    }

    public function categorieEnquete()
    {
        return $this->belongsTo(ConfigTbl_Categories_enquetes::class, 'categories_enquetes_id');
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
            $prefix = 'ENQUETE-';
            $timestamp = now()->format('ymdHi');

            $random = strtoupper(Str::random(7));
            $examenPhysique->code = $prefix . $timestamp . $random;
        });
    }
    //
}
