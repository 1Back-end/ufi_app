<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConfigTblCategoriesExamenPhysique extends Model
{
    protected $table = 'config_tbl_categories_examen_physiques';

    protected $fillable = [
        'name',
        'description',
        'is_deleted',
        'created_by',
        'updated_by',
        'order',
        'code',
        'is_active'
    ];
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $year = now()->format('Y');
            $today = now()->format('Ymd');

            $lastRecord = self::whereYear('created_at', $year)
                ->orderBy('id', 'desc')
                ->first();

            $sequence = $lastRecord ? intval(substr($lastRecord->code, 4, 3)) + 1 : 1;
            $formattedSequence = str_pad($sequence, 3, '0', STR_PAD_LEFT);

            $model->code = '#' . $formattedSequence  . $today;
        });
    }
}
