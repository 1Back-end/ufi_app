<?php

namespace App\Models;

use App\Http\Controllers\OrdonnanceController;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class RapportActe extends Model
{
    use HasFactory,SoftDeletes;

    protected $table = 'consultation_report_actes';

    protected $fillable = [
        'rapport_consultation_id',
        'acte_id',
        'name',
        'type',
        'description',
        'created_by',
        'updated_by',
        'code'
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($examenPhysique) {
            $year = now()->format('Y');
            $today = now()->format('Ymd');

            $lastRecord = self::whereYear('created_at', $year)
                ->orderBy('id', 'desc')
                ->first();

            $sequence = $lastRecord ? intval(substr($lastRecord->code, 4, 3)) + 1 : 1;
            $formattedSequence = str_pad($sequence, 3, '0', STR_PAD_LEFT);

            $examenPhysique->code = '#' . $formattedSequence  . $today;
        });
    }

    public function rapport_consultation()
    {
        return $this->belongsTo(OpsTblRapportConsultation::class, 'rapport_consultation_id');
    }

    public function acte()
    {
        return $this->belongsTo(Acte::class, 'acte_id');
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
