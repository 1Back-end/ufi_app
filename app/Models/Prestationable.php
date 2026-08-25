<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Prestationable extends Model
{
    use HasFactory, SoftDeletes;
    public $timestamps = false;

    protected $fillable = [
        'prestation_id', 'prestationable_id', 'prestationable_type', 'remise', 'quantity',
        'date_rdv', 'date_rdv_end', 'nbr_days', 'type_salle', 'honoraire', 'created_at', 'updated_at',
        'amount_regulate', 'pu', 'b', 'k_modulateur', 'prelevements', 'status_examen','amount_prorate','amount_contested','amount_contested',
        'consultant_amount_status','is_preleve', 'prelevement_count','is_repreleve','repreleve_date','is_result_entered','validated_at',
        'printed_at',
        'validated_by',
        'printed_by',
        'prelevate_at',
        'prelevated_by'
    ];

    protected $casts = [
        'prelevements' => 'array',
        'validated_at' => 'datetime',
        'printed_at' => 'datetime',
        'prelevate_at' => 'datetime',
    ];

    public function prestation(): BelongsTo
    {
        return $this->belongsTo(Prestation::class, 'prestation_id');
    }
    public function validator()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function printer()
    {
        return $this->belongsTo(User::class, 'printed_by');
    }
    public function prelevate(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prelevated_by');
    }
}
