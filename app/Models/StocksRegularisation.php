<?php
namespace App\Models;

use App\Enums\StocksAdjustmentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Enums\StockAdjustmentAction;

class StocksRegularisation extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'stocks_regularisations';

    protected $fillable = [
        'status',
        'action',
        'updated_by',
        'created_by',
        'emplacement_id',
        'validated_at',
        'validated_by'
    ];

    protected $casts = [
        'action' => StockAdjustmentAction::class,
    ];

    protected $appends = ['status_label','action_label'];

    public function getStatusLabelAttribute(): string
    {
        return StocksAdjustmentStatus::safeLabel($this->status);
    }
    public function getActionLabelAttribute(): string
    {
        if ($this->action instanceof StockAdjustmentAction) {
            return $this->action->label();
        }

        return StockAdjustmentAction::safeLabel($this->action);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function emplacement(): BelongsTo
    {
        return $this->belongsTo(EmplacementsProduct::class, 'emplacement_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StocksRegularisationsItem::class, 'stocks_regularisation_id');
    }
    public function validator(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }
}
