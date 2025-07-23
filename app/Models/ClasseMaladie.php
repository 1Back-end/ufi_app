<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
class ClasseMaladie extends Model
{
    protected $table = 'classe_maladie';

    protected $fillable = ['code', 'name', 'created_by', 'updated_by','is_deleted'];



    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
