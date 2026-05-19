<?php

namespace App\Models;

use App\Enums\PaymentAccountType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PaymentAccount extends Model
{
    use SoftDeletes;

    protected $table = 'payment_accounts';

    protected $fillable = [
        'name',
        'code',
        'description',
        'is_used_for_consultant',
        'is_active',
        'created_by',
        'updated_by',
        'account_type'
    ];

    protected $casts = [
        'is_used_for_consultant' => 'boolean',
        'is_active' => 'boolean',
    ];

    protected $appends = ['status_label'];

    public function getStatusLabelAttribute(): string
    {
        return PaymentAccountType::safeLabel($this->account_type);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
