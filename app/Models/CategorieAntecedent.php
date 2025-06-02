<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CategorieAntecedent extends Model
{
    protected $table = 'config_tbl_categorie_antecedents';

    protected $fillable = [
        'name',
        'souscategorie_antecedent_id',
        'description',
        'created_by',
        'updated_by',
        'is_deleted',
        'status'
    ];

    protected $casts = [
        'is_deleted' => 'boolean',
    ];

    public function sousCategorieAntecedent()
    {
        return $this->belongsTo(ConfigTblSousCategorieAntecedent::class, 'souscategorie_antecedent_id');
    }

    // Relation vers l'utilisateur qui a créé l'entrée
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Relation vers l'utilisateur qui a mis à jour l'entrée
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    //
    //
}
