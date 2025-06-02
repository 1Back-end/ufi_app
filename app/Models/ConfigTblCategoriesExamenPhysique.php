<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfigTblCategoriesExamenPhysique extends Model
{
    protected $table = 'config_tbl_categories_examen_physiques';

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
