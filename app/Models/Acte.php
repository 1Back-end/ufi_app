<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Acte extends Model
{
    use HasFactory;

    protected $fillable = [
        'created_by',
        'updated_by',
        'name',
        'pu',
        'type_acte_id',
        'delay',
        'state',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function typeActe(): BelongsTo
    {
        return $this->belongsTo(TypeActe::class);
    }

    protected function casts(): array
    {
        return [
            'state' => 'boolean',
        ];
    }
}
