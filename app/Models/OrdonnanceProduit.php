<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrdonnanceProduit extends Model
{
    use HasFactory;

    protected $table = 'ordonnance_produits'; // nom de la table

    protected $fillable = [
        'ordonnance_id',
        'nom',
        'quantite',
        'protocole',
        'created_by',
        'updated_by',
    ];


    public function ordonnance()
    {
        return $this->belongsTo(Ordonnance::class, 'ordonnance_id');
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
