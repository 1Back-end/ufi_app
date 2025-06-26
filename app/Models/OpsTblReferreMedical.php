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
    //
}
