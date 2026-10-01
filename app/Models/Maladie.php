<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Maladie extends Model
{
    use HasFactory,SoftDeletes;

    protected $table = 'diseases';

    protected $fillable = [
        'classe_maladie_id',
        'groupe_maladie_id',
        'code',
        'name',
        'is_active',
        'is_deleted',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_deleted' => 'boolean',
    ];

    public function classeMaladie()
    {
        return $this->belongsTo(ClasseMaladie::class, 'classe_maladie_id');
    }

    public function groupeMaladie()
    {
        return $this->belongsTo(GroupeMaladie::class, 'groupe_maladie_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    //
}
