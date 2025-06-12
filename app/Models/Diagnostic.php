<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Diagnostic extends Model
{
    use HasFactory;

    protected $table = 'ops_tbl_diagnostic';

    protected $fillable = [
        'code',
        'rapport_consultations_id',
        'type_diagnostic_id',
        'description',
        'created_by',
        'updated_by',
    ];

    // Relations

    public function rapportConsultation()
    {
        return $this->belongsTo(OpsTblRapportConsultation::class, 'rapport_consultations_id');
    }

    public function typeDiagnostic()
    {
        return $this->belongsTo(ConfigTbl_Type_Diagnostic::class, 'type_diagnostic_id');
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

        static::creating(function ($diagnostic) {
            $prefix = 'DIAGNOSTIC-';
            $timestamp = now()->format('YmdHis');
            $diagnostic->code = $prefix . $timestamp;
        });
    }
    //
}
