<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfigTbl_Type_Diagnostic extends Model
{
    protected $table = 'configtbl_type_diagnostic';

    protected $fillable = [
        'name',
        'description',
        'is_deleted',
        'created_by',
        'updated_by',
    ];
    public function diagnostics()
    {
        return $this->hasMany(Diagnostic::class, 'code_diagnostic_id');
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
