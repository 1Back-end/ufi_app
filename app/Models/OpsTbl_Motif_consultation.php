<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OpsTbl_Motif_consultation extends Model
{
    use HasFactory, SoftDeletes;

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
