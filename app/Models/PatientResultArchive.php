<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PatientResultArchive extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'patient_result_archives';

    protected $fillable = [
        'prestation_id',
        'count',
        'created_by',
        'updated_by',
    ];

    public function prestation()
    {
        return $this->belongsTo(Prestation::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
