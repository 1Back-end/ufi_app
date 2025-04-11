<?php

namespace App\Models;

use App\Enums\TypePrestation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Prestation extends Model
{
    use HasFactory;

    protected $fillable = [
        'prise_charge_id',
        'client_id',
        'consultant_id',
        'assureur',
        'payable',
        'payable_by',
        'programmation_date',
        'created_by',
        'updated_by',
        'type'
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function consultant(): BelongsTo
    {
        return $this->belongsTo(Consultant::class);
    }

    public function payableBy(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'payable_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'payable' => 'boolean',
            'programmation_date' => 'datetime',
            'type' => TypePrestation::class
        ];
    }
}
