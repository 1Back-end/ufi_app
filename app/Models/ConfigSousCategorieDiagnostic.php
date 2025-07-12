<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class ConfigSousCategorieDiagnostic extends Model
{
    use HasFactory;

    protected $table = 'config_sous_categorie_diagnostic';

    protected $fillable = [
        'categorie_id',
        'name',
        'is_deleted',
        'created_by',
        'updated_by',
    ];


    public function categorie()
    {
        return $this->belongsTo(CategorieDiagnostic::class, 'categorie_id');
    }


    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }


    public function editor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    public function maladies()
    {
        return $this->hasMany(ConfigTblMaladieDiagnostic::class, 'sous_categorie_id');
    }

}
