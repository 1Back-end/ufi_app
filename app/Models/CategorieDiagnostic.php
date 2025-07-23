<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CategorieDiagnostic extends Model
{
    use HasFactory;
    protected $table = 'categorie_diagnostic';

    protected $fillable = [
        'name',
        'has_nosologies',
        'is_deleted',
        'created_by',
        'updated_by',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    public function sousCategories()
    {
        return $this->hasMany(ConfigSousCategorieDiagnostic::class, 'categorie_id');
    }
    public function diagnostics()
    {
        return $this->belongsToMany(
            Diagnostic::class,
            'ops_tbl_diagnostic_has_config_categorie_diagnostic',
            'categorie_diagnostic_id',
            'code_diagnostic_id'
        );
    }

    //
}
