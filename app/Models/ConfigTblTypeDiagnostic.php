<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class ConfigTblTypeDiagnostic extends Model
{
    use HasFactory;

    protected $table = 'config_tbl_type_diagnostic';

    protected $fillable = [
        'code',
        'description',
        'created_by',
        'updated_by',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($type_diagnostic) {
            do {
                $prefix = 'TD-';
                $timestamp = now()->format('YmdHis');
                $random = strtoupper(Str::random(4));
                $code = $prefix . $timestamp . '-' . $random;
            } while (self::where('code', $code)->exists());

            $type_diagnostic->code = $code;
        });
    }
    //
}
