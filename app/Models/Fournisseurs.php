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
        'email',
        'status',               // "actif" ou "inactif"
        'is_deleted',
        'created_by',
        'updated_by',
        'registre_commerce',
        'nui',
        'personne_contact_1',
        'telephone_contact_1',
        'personne_contact_2',
        'telephone_contact_2',
        'directeur_general',
    ];

    public function fournisseurs()
    {
        return $this->belongsToMany(Fournisseurs::class, 'product_fournisseur');
    }
    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_fournisseur');
    }

    // Relations (par exemple, pour 'created_by' et 'updated_by' avec l'utilisateur)
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    // Dans le modèle Fournisseurs


    public function updator()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Vous pouvez aussi ajouter des méthodes pour manipuler la suppression logique
    public function scopeNotDeleted($query)
    {
        return $query->where('is_deleted', false);
    }
}
