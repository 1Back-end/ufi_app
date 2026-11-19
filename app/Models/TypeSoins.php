<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TypeSoins extends Model
{
    use HasFactory,SoftDeletes;
    protected $fillable = [
        'name',
        'order',
        'created_by',
        'updated_by',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    public function soins(): HasMany
    {
        return $this->hasMany(Soins::class, 'type_soin_id');
    }
}
