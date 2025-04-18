<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Typeconsultation extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'created_by',
        'updated_by',
    ];

    // Relation avec l'utilisateur qui a créé
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Relation avec l'utilisateur qui a modifié
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    //
}
