<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DossierLocation extends Model
{
    use HasFactory;

    protected $table = 'dossier_locations';

    protected $fillable = [
        'name',
        'code',
        'room',
        'shelf',
        'description',
        'is_active',
        'updated_by',
        'created_by',
        'centre_id',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function centre()
    {
        return $this->belongsTo(Centre::class, 'centre_id');
    }
}
