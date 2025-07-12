<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class ConfigTblCategorieVisite extends Model
{
    use HasFactory;

    protected $table = 'config_tbl_categorie_visites';

    protected $fillable = [
        'libelle',
        'type_visite_id',
        'description',
        'is_active',
        'is_deleted',
        'created_by',
        'updated_by',
        'sous_type'
    ];


    public function typeVisite()
    {
        return $this->belongsTo(ConfigTblTypeVisite::class, 'type_visite_id');
    }

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
