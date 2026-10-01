<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
class ClasseMaladie extends Model
{
    use HasFactory,SoftDeletes;
    protected $table = 'disease_classes';

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
