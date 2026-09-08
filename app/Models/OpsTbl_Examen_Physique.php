<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class OpsTbl_Examen_Physique extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'ops_tbl__examen__physiques';

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

        static::creating(function ($model) {
            $year = now()->format('Y');
            $today = now()->format('Ymd');

            $lastRecord = self::whereYear('created_at', $year)
                ->orderBy('id', 'desc')
                ->first();

            $sequence = $lastRecord ? intval(substr($lastRecord->code, 4, 3)) + 1 : 1;
            $formattedSequence = str_pad($sequence, 3, '0', STR_PAD_LEFT);

            $model->code = '#' . $formattedSequence  . $today;
        });
    }
}
