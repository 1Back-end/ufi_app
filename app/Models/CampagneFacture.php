<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CampagneFacture extends Model
{
    use HasFactory,SoftDeletes;

    protected $table = 'campagne_factures';

    protected $fillable = [
        'code',
        'campagne_id',
        'patient_id',
        'consultant_id',
        'centre_id',
        'amount',
        'status',
        'billing_date',
        'created_by',
        'updated_by',
        'facturation_date'
    ];

    // 🔹 Relations
    public function campagne()
    {
        return $this->belongsTo(Campagne::class, 'campagne_id');
    }

    public function patient()
    {
        return $this->belongsTo(Client::class, 'patient_id');
    }

    public function consultant()
    {
        return $this->belongsTo(Consultant::class, 'consultant_id');
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
