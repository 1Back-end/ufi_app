<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
class GroupeMaladie extends Model
{
    protected $table = 'groupes_maladies';

    protected $fillable = [
        'classe_maladie_id',
        'code',
        'name',
        'created_by',
        'updated_by',
        'is_active',
        'is_deleted',
    ];


    public function classeMaladie()
    {
        return $this->belongsTo(ClasseMaladie::class, 'classe_maladie_id');
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
