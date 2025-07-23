<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class BilanActeRendezVous extends Model
{
    use HasFactory;

    protected $table = 'bilans_actes_rendez_vous';

    protected $fillable = [
        'rendez_vous_id',
        'prestation_id',
        'consultant_id',
        'technique_analyse_id',
        'resume',
        'conclusion',
        'created_by',
        'updated_by',
    ];

    public function rendezVous()
    {
        return $this->belongsTo(RendezVous::class, 'rendez_vous_id');
    }

    public function prestation()
    {
        return $this->belongsTo(Prestation::class, 'prestation_id');
    }
    public function consultant()
    {
        return $this->belongsTo(Consultant::class, 'consultant_id');
    }
    public function techniqueAnalyse()
    {
        return $this->belongsTo(AnalysisTechnique::class, 'technique_analyse_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    //
}
