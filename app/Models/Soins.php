<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Soins extends Model
{
    use HasFactory;

    protected $fillable = [
        'type_soin_id',
        'pu',
        'pu_default',
        'name',
        'status',
        'created_by',
        'updated_by',
        'is_deleted',
    ];

    // Relations

    public function type_soins()
    {
        return $this->belongsTo(TypeSoins::class, 'type_soin_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    //
}
