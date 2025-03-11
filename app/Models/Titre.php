<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Titre extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom_titre',
        'abbreviation_titre',
        'create_by',
        'update_by',
    ];

    public function createBy()
    {
        return $this->belongsTo(User::class, 'create_by');
    }

    public function updateBy()
    {
        return $this->belongsTo(User::class, 'update_by');
    }
}
