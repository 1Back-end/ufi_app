<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Campagne extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'title', 'description', 'price',
        'start_date', 'end_date', 'status', 'is_deleted',
        'centre_id', 'created_by', 'updated_by','abbreviation_unique','full_name'
    ];

    protected $casts = [
        'price' => 'float',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_deleted' => 'boolean',
    ];

    public function elements()
    {
        return $this->hasMany(CampagneElement::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function centre()
    {
        return $this->belongsTo(Centre::class);
    }
}
