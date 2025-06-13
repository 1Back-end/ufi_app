<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OpsTblCertificatMedical extends Model
{
    protected $table = 'ops_tbl_certificat_medical';

    protected $fillable = [
        'code',
        'type',
        'commentaire',
        'nbre_jour_repos',
        'motif_consultation_id',
        'is_deleted',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_deleted' => 'boolean',
    ];

    // Relations
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

        static::creating(function ($certificatMedical) {
            $prefix = 'CERTIFICATE-';
            $timestamp = now()->format('YmdHis');
            $certificatMedical->code = $prefix . $timestamp;
        });
    }
    //
    //
}
