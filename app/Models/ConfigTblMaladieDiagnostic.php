<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class ConfigTblMaladieDiagnostic extends Model
{
    use HasFactory;

    protected $table = 'config_tbl_maladie_diagnostic';

    protected $fillable = [
        'sous_categorie_id',
        'name',
        'is_deleted',
        'created_by',
        'updated_by',
    ];

    public function sousCategorie()
    {
        return $this->belongsTo(ConfigSousCategorieDiagnostic::class, 'sous_categorie_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
