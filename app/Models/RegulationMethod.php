<?php

namespace App\Models;

use App\Enums\TypeRegulation;
use App\Models\Trait\UpdatingUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RegulationMethod extends Model
{
    use UpdatingUser;

    protected $fillable = [
        'name',
        'description',
        'active',
        'comment_required',
        'type_regulation',
        'phone_method',
        'created_by',
        'updated_by',
    ];

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
            'active' => 'boolean',
            'comment_required' => 'boolean',
            'type_regulation' => TypeRegulation::class,
            'phone_method' => 'boolean',
        ];
    }
}
