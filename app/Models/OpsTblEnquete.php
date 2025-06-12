<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OpsTblEnquete extends Model
{
    protected $table = 'ops_tbl_enquetes';

    protected $fillable = [
        'code',
        'libelle',
        'resultat',
        'motif_consultation_id',
        'categories_enquetes_id',
        'is_deleted',
        'created_by',
        'updated_by',
    ];


    public function motifConsultation()
    {
        return $this->belongsTo(OpsTbl_Motif_consultation::class, 'motif_consultation_id');
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
            $prefix = 'EXAMEN-ENQUETE-';
            $timestamp = now()->format('YmdHis');
            $examenPhysique->code = $prefix . $timestamp;
        });
    }
    //
}
