<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Titre extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'consultant_titles';

    protected $fillable = [
        'nom_titre',
        'abbreviation_titre',
        'created_by',
        'updated_by',
        'is_active',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
