<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CentrePrestation extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'centre_prestations';

    protected $fillable = [
        'centre_id',
        'name',
        'is_active',
        'created_by',
        'updated_by',
    ];

    public function centre()
    {
        return $this->belongsTo(Centre::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
