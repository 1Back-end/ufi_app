<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OpsTblRapportConsultation extends Model
{
    protected $fillable = [
        'code',
        'conclusion',
        'recommandations',
        'motif_consultation_id',
        'is_deleted',
        'created_by',
        'updated_by'
    ];

    public function motifConsultation()
    {
        return $this->belongsTo(OpsTbl_Motif_consultation::class, 'motif_consultation_id');
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

        static::creating(function ($rapport) {
            $prefix = 'RAPPORT-';
            $timestamp = now()->format('YmdHis');
            $rapport->code = $prefix . $timestamp;
        });
    }
    //
}
