<?php

declare(strict_types=1);

namespace App\Modules\Wallets\Models;

use App\Modules\Users\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransactionAudit extends Model
{
    use HasUuids;

    public $timestamps = false;

    protected $fillable = [
        'wallet_transaction_id',
        'action',
        'snapshot_before',
        'snapshot_after',
        'actor_user_id',
        'ip_address',
        'created_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'snapshot_before' => 'array',
            'snapshot_after' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function walletTransaction(): BelongsTo
    {
        return $this->belongsTo(WalletTransaction::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
