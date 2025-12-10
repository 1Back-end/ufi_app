<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FacturationAssurance extends Model
{
    use HasFactory;

    protected $table = 'facturation_assurance';

    protected $fillable = [
        'start_date',
        'end_date',
        'assurance',
        'facture_number',
        'amount',
        'created_by',
        'updated_by',
        'centre_id',
        'assurance_id'
    ];



    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    public function assurance()
    {
        return $this->belongsTo(Assureur::class, 'assurance_id');
    }
    public function centre()
    {
        return $this->belongsTo(Centre::class, 'centre_id');
    }
    //
}
