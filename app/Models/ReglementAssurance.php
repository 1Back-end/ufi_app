<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ReglementAssurance extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'amount_total',
        'ir_total',
        'net_amount',
        'apply_ir_global',
        'ir_rate_global',
        'assurance_id',
        'type',
        'reglement_date_sart',
        'reglement_date_end',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'apply_ir_global' => 'boolean',
        'reglement_date_sart' => 'datetime',
        'reglement_date_end' => 'datetime',
    ];

    public function assurance()
    {
        return $this->belongsTo(Assureur::class);
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
