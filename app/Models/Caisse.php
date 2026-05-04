<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Caisse extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'caisses';

    protected $fillable = [
        'code',
        'name',
        'is_active',
        'created_by',
        'updated_by',
        'centre_id',
        'description',
        'is_primary',
        'position',
        'user_id',
        'type_caisse',
        'secret_code',
        'solde_caisse',
        'is_default_secret_code',
        'can_start_session',
        'session_control_expires_at',
        'session_control_status',
        'small_change'
    ];
    protected $hidden = [
        'secret_code',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($caisse) {
            $caisse->code = self::generateCode();
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
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function sessions()
    {
        return $this->hasMany(SessionCaisse::class, 'caisse_id');
    }
    public function sessionOuverte()
    {
        return $this->hasOne(SessionCaisse::class, 'caisse_id')->where('etat', 'OUVERTE');
    }
}
