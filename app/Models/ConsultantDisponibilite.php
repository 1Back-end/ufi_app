<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConsultantDisponibilite extends Model
{
    use HasFactory;

    protected $fillable = [
        'consultant_id',
        'jour',
        'heure',
        'is_deleted',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'heure' => 'datetime:H:i',
    ];

    public function consultant()
    {
        return $this->belongsTo(Consultant::class);
    }

    public function createBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updateBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
