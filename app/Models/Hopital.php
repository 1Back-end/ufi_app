<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Hopital extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom_hopi',
        'Abbreviation_hopi',
        'addresse_hopi',
        'created_by',
        'updated_by',
    ];

    public function createByHopi()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updateByHopi()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
