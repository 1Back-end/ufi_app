<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Specialite extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom_specialite',
        'created_by',
        'updated_by',
    ];

    public function createBySpecialite()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updateBySpecialite()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
