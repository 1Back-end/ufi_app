<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ConfigTblTypeVisite extends Model
{
    use HasFactory;

    protected $table = 'config_tbl_type_visite';

    protected $fillable = [
        'libelle',
        'description',
        'is_active',
        'is_deleted',
        'created_by',
        'updated_by',
    ];

    // 🔗 Relations avec l'utilisateur qui a créé et modifié
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    //
}
