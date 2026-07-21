<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductType extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'accepts_galenic_form',
        'accepts_generic_form',
        'accepts_packaging',
        'created_by',
        'updated_by',
        'is_active',
    ];

    protected $casts = [
        'accepts_galenic_form' => 'boolean',
        'accepts_generic_form' => 'boolean',
        'accepts_packaging' => 'boolean',
        'is_active' => 'boolean',
    ];
    public function creator(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
