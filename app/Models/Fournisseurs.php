<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fournisseurs extends Model
{
    use HasFactory;

    protected $table = 'fournisseurs';

    protected $fillable = [
        'nom',
        'adresse',
        'tel',
        'fax',
        'email',
        'ville',
        'pays',
        'state',
        'is_deleted',
        'created_by',
        'updated_by',
    ];

    // Relations (par exemple, pour 'created_by' et 'updated_by' avec l'utilisateur)
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Vous pouvez aussi ajouter des méthodes pour manipuler la suppression logique
    public function scopeNotDeleted($query)
    {
        return $query->where('is_deleted', false);
    }
}
