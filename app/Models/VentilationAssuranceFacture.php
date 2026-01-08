<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VentilationAssuranceFacture extends Model
{
    use HasFactory;

    // Table associée
    protected $table = 'ventilations_assurances_factures';

    // Champs assignables
    protected $fillable = [
        'id',
        'ventilation_date',
        'piece_number',
        'piece_date',
        'total_amount',
        'comment',
        'regulation_method_id',
        'first_facture_date',
        'last_facture_date',
        'created_by',
        'updated_by',
    ];

    // Génération automatique de UUID
    protected static function booted()
    {

    }

    // Relations
    public function regulationMethod()
    {
        return $this->belongsTo(RegulationMethod::class, 'regulation_method_id');
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
