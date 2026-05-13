<?php

declare(strict_types=1);

namespace App\Modules\Wallets\Models;

use App\Enums\WalletTransactionDirection;
use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class WalletTransaction extends Model
{
    use HasUuids;

    protected $fillable = [
        'wallet_id',
        'type',
        'status',
        'amount_minor',
        'direction',
        'balance_after_minor',
        'reserved_after_minor',
        'reference_type',
        'reference_id',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => WalletTransactionType::class,
            'status' => WalletTransactionStatus::class,
            'direction' => WalletTransactionDirection::class,
            'amount_minor' => 'integer',
            'balance_after_minor' => 'integer',
            'reserved_after_minor' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function audits(): HasMany
    {
        return $this->hasMany(WalletTransactionAudit::class);
    }
}
