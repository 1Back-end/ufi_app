<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
            $prefix = 'TD-';
            $timestamp = now()->format('YmdHis');
            $type_diagnostic->code = $prefix . $timestamp;
        });
    }
    //
}
