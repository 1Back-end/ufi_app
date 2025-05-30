<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OpsTblAntecedent extends Model
{
    protected $table = 'ops_tbl_antecedents';

    protected $fillable = [
        'client_id',
        'categorie_antecedent_id',
        'souscategorie_antecedent_id',
        'description',
        'is_deleted',
        'created_by',
        'updated_by',
    ];

    // Relations
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function categorie(): BelongsTo
    {
        return $this->belongsTo(CategorieAntecedent::class, 'categorie_antecedent_id');
    }

    public function sousCategorie(): BelongsTo
    {
        return $this->belongsTo(ConfigTblSousCategorieAntecedent::class, 'souscategorie_antecedent_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    //
}
