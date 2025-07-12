<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OpsTblReferreMedical extends Model
{
    protected $table = 'ops_tbl_referre_medical';

    protected $fillable = [
        'rapport_consultations_id',
        'description',
        'code_prescripteur',
        'type_prescripteur',
        'consultant_id',
        'prescripteur_id',
        'created_by',
        'updated_by',
        'is_deleted',
    ];


    public function rapportConsultation(): BelongsTo
    {
        return $this->belongsTo(OpsTblRapportConsultation::class, 'rapport_consultations_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }


    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }


    public function prescripteur(): BelongsTo
    {
        return $this->belongsTo(Prescripteur::class, 'prescripteur_id');
    }


    public function consultant(): BelongsTo
    {
        return $this->belongsTo(Consultant::class, 'consultant_id');
    }


    public static function generateCodePrescripteur(): string
    {
        $prefix = 'PR-';
        $date = now()->format('Ymd');
        $random = strtoupper(substr(uniqid(), -5));

        return $prefix . $date . '-' . $random;
    }
}
