<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Service_Hopital extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom_service_hopi',
        'created_by',
        'updated_by',
    ];

    public function createByServiceHopi()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updateByServiceHopi()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
