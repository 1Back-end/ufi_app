<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ConsultantPaymentPrestation extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'consultant_payments';

    protected $fillable = [
        'consultant_id',
        'account_id',
        'start_date',
        'end_date',
        'description',
        'amount',
        'caisse_id',
        'created_by',
        'updated_by',
        'centre_id'
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'amount' => 'float',
    ];

    public function consultant()
    {
        return $this->belongsTo(Consultant::class,'consultant_id');
    }

    public function account()
    {
        return $this->belongsTo(PaymentAccount::class, 'account_id');
    }

    public function caisse()
    {
        return $this->belongsTo(Caisse::class, 'caisse_id');
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

}
