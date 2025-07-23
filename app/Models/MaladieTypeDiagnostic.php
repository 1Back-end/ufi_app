<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
class MaladieTypeDiagnostic extends Model
{
    use HasFactory;

    protected $table = 'maladie_type_diagnostic';

    protected $fillable = [
        'maladie_id',
        'type_diagnostic_id',
        'rapport_consultations_id',
        'description',
        'is_deleted',
        'created_by',
        'updated_by',
    ];

    // Relations
    public function maladie()
    {
        return $this->belongsTo(Maladie::class);
    }

    public function typeDiagnostic()
    {
        return $this->belongsTo(ConfigTbl_Type_Diagnostic::class, 'type_diagnostic_id');
    }
    public function RapportConsultation()
    {
        return $this->belongsTo(OpsTblRapportConsultation::class,'rapport_consultations_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    //
}
