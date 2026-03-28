<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransfertFonds extends Model
{
    use HasFactory,SoftDeletes;

    protected $table = 'transferts_fonds';

    protected $fillable = [
        'code',
        'caisse_depart_id',
        'caisse_reception_id',
        'status',
        'montant_send',
        'send_by',
        'created_by',
        'updated_by',
        'centre_id',
        'type',
        'validated_by',
        'validated_at',
        'rejected_by',
        'rejected_at',

    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->code = self::generateCode();
        });
    }

    public static function generateCode(): string
    {
        $datePart = now()->format('Ydm'); // Année, jour, mois → ex: 20252611
        $prefix = '#' . $datePart;

        // Chercher le dernier code global (pas par jour)
        $last = self::withTrashed()->orderBy('created_at', 'desc')->first();

        if ($last && preg_match('/(\d{6})$/', $last->code, $matches)) {
            $number = (int) $matches[1] + 1;
        } else {
            $number = 1;
        }

        return $prefix . str_pad($number, 6, '0', STR_PAD_LEFT);
    }

    // 🔹 Relations
    public function caisse_depart()
    {
        return $this->belongsTo(Caisse::class, 'caisse_depart_id');
    }

    public function caisse_reception()
    {
        return $this->belongsTo(Caisse::class, 'caisse_reception_id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'send_by');
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
        return $this->belongsTo(Centre::class, 'centre_id');
    }

    public function validated()
    {
        return $this->belongsTo(User::class, 'validated_by');
    }
    public function rejected()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }
}
