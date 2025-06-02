<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfigTblSousCategorieAntecedent extends Model
{
    protected $table = 'configtbl_souscategorie_antecedent';

    protected $fillable = [
        'name',
        'description',
        'is_deleted',
        'created_by',
        'updated_by',
    ];

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
