<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FactureAssuranceByCentre extends Model
{
    use HasFactory;

    protected $table = 'facture_assurances_by_centre';

    protected $fillable = [
        'centre_id',
        'object_of_facture_assurance',
        'mode_of_payment',
        'compte_or_payment',
        'number_for_compte',
        'text_of_remerciement',
        'created_by',
        'updated_by',
    ];

    public function centre()
    {
        return $this->belongsTo(Centre::class, 'centre_id');
    }


    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedByUser()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
