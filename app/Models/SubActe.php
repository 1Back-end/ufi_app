<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubActe extends Model
{
    use HasFactory;

    protected $table = 'sub_act_categories'; // le nom de ta table
    protected $fillable = [
        'type_acte_id',
        'name',
        'is_active',
        'created_by',
        'updated_by'
    ];

    // Relation vers la table Actes
    public function type_acte()
    {
        return $this->belongsTo(TypeActe::class, 'type_acte_id');
    }

    // Relation vers l'utilisateur qui a créé
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Relation vers l'utilisateur qui a modifié
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
