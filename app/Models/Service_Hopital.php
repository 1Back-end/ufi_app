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
        'create_by_service_hopi',
        'update_by_service_hopi',
    ];

    public function createByServiceHopi()
    {
        return $this->belongsTo(User::class, 'create_by_service_hopi');
    }

    public function updateByServiceHopi()
    {
        return $this->belongsTo(User::class, 'update_by_service_hopi');
    }
}
