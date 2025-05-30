<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfigTbl_Categories_enquetes extends Model
{
    protected $table = 'configtbl_categories_enquetes';

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
