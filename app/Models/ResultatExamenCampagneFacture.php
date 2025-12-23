<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ResultatExamenCampagneFacture extends Model
{
    protected $table = 'resultats_examens_campagne_factures'; // ⚡ le nom exact
    use HasFactory;
    protected $fillable = [
        'consultant_id',
        'patient_id',
        'facture_campagne_id',
        'centre_id',
        'created_by',
        'updated_by',
        'examens',
        'reference',
        'status',
        'prelevement_date'
    ];

    protected $casts = [
        'examens' => 'array',
    ];

    // Relations
    public function consultant()
    {
        return $this->belongsTo(Consultant::class, 'consultant_id');
    }

    public function patient()
    {
        return $this->belongsTo(Client::class, 'patient_id');
    }

    public function factureCampagne()
    {
        return $this->belongsTo(CampagneFacture::class, 'facture_campagne_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    public function centre()
    {
        return $this->belongsTo(Centre::class, 'centre_id');
    }


}
