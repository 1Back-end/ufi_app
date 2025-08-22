<?php

namespace App\Models;

use App\Http\Controllers\OrdonnanceController;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class RapportActe extends Model
{
    use HasFactory;

    protected $table = 'rapports_actes';

    protected $fillable = [
        'rapport_consultation_id',
        'acte_id',
        'name',
        'type',
        'description',
        'created_by',
        'updated_by',
    ];

    public function rapport_consultation()
    {
        return $this->belongsTo(OpsTblRapportConsultation::class, 'rapport_consultation_id');
    }

    public function acte()
    {
        return $this->belongsTo(Acte::class, 'acte_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
