<?php

namespace App\Models;

use App\Models\Trait\UpdatingUser;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConventionAssocie extends Model
{
    use UpdatingUser;

    protected $fillable = [
        'client_id',
        'date',
        'amount_max',
        'start_date',
        'end_date',
        'active',
        'created_by',
        'updated_by',
    ];

    protected function amountMax(): Attribute
    {
        return Attribute::make(
            get: fn($value, array $attributes) => $value / 100,
            set: fn($value) => $value * 100,
        );
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'start_date' => 'date',
            'end_date' => 'date',
            'active' => 'boolean'
        ];
    }
}
