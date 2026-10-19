<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientAccessCode extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'patient_access_codes';

    protected $fillable = [
        'prestation_id',
        'client_id',
        'code',
        'token',
        'expires_at',
        'is_used',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_used' => 'boolean',
    ];

    public function prestation(): BelongsTo
    {
        return $this->belongsTo(Prestation::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
