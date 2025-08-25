<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class ExamenActes extends Model
{
    use HasFactory;

    protected $table = 'examens_actes';
    protected $fillable = [
        'rapport_consultation_id',
        'examen_id',
        'name',
        'type',
        'description',
        'created_by',
        'updated_by',
    ];

    public function rapport_consultation(){
        return $this->belongsTo(OpsTblRapportConsultation::class, 'rapport_consultation_id');
    }
    public function examen(){
        return $this->belongsTo(Examen::class, 'examen_id');
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
