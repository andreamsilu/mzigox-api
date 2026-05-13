<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\WalletTransactionDirection;
use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use App\Exceptions\DomainException;
use App\Modules\Trips\Models\Trip;
use App\Modules\Users\Models\User;
use App\Modules\Wallets\Models\Wallet;
use App\Modules\Wallets\Models\WalletTransaction;
use App\Modules\Wallets\Models\WalletTransactionAudit;
use Illuminate\Support\Facades\DB;

final class WalletService
{
    public function getOrCreateWallet(User $user): Wallet
    {
        return Wallet::query()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'balance_minor' => 0,
                'reserved_balance_minor' => 0,
                'currency' => 'TZS',
            ]
        );
    }

    public function assertAvailableMinor(Wallet $wallet, int $amountMinor): void
    {
        $available = $wallet->balance_minor - $wallet->reserved_balance_minor;
        if ($available < $amountMinor) {
            throw new DomainException('Insufficient wallet balance for this operation.');
        }
    }

    /**
     * Move funds from spendable balance into reserved balance (commission hold).
     */
    public function reserveCommissionMinor(Wallet $wallet, int $amountMinor, Trip $trip, ?string $actorIp = null): WalletTransaction
    {
        if ($amountMinor <= 0) {
            throw new DomainException('Commission amount must be positive.');
        }

        return DB::transaction(function () use ($wallet, $amountMinor, $trip, $actorIp): WalletTransaction {
            /** @var Wallet $locked */
            $locked = Wallet::query()->whereKey($wallet->id)->lockForUpdate()->firstOrFail();
            $this->assertAvailableMinor($locked, $amountMinor);

            $before = [
                'balance_minor' => $locked->balance_minor,
                'reserved_balance_minor' => $locked->reserved_balance_minor,
            ];

            $locked->balance_minor -= $amountMinor;
            $locked->reserved_balance_minor += $amountMinor;
            $locked->save();

            $txn = WalletTransaction::query()->create([
                'wallet_id' => $locked->id,
                'type' => WalletTransactionType::Commission,
                'status' => WalletTransactionStatus::Pending,
                'amount_minor' => $amountMinor,
                'direction' => WalletTransactionDirection::Debit,
                'balance_after_minor' => $locked->balance_minor,
                'reserved_after_minor' => $locked->reserved_balance_minor,
                'reference_type' => 'trip',
                'reference_id' => $trip->id,
                'metadata' => ['phase' => 'reserve', 'trip_id' => $trip->id],
            ]);

            $this->writeAudit($txn, 'commission_reserved', $before, [
                'balance_minor' => $locked->balance_minor,
                'reserved_balance_minor' => $locked->reserved_balance_minor,
            ], $actorIp);

            return $txn;
        });
    }

    /**
     * Release a pending commission hold back to spendable balance.
     */
    public function releaseCommissionReservationMinor(Wallet $wallet, Trip $trip, ?string $actorIp = null): void
    {
        DB::transaction(function () use ($wallet, $trip, $actorIp): void {
            /** @var Wallet $locked */
            $locked = Wallet::query()->whereKey($wallet->id)->lockForUpdate()->firstOrFail();

            $pending = WalletTransaction::query()
                ->where('wallet_id', $locked->id)
                ->where('reference_type', 'trip')
                ->where('reference_id', $trip->id)
                ->where('type', WalletTransactionType::Commission)
                ->where('status', WalletTransactionStatus::Pending)
                ->lockForUpdate()
                ->first();

            if (! $pending) {
                return;
            }

            $amount = $pending->amount_minor;
            $before = [
                'balance_minor' => $locked->balance_minor,
                'reserved_balance_minor' => $locked->reserved_balance_minor,
            ];

            $locked->balance_minor += $amount;
            $locked->reserved_balance_minor -= $amount;
            $locked->save();

            $pending->status = WalletTransactionStatus::Released;
            $pending->balance_after_minor = $locked->balance_minor;
            $pending->reserved_after_minor = $locked->reserved_balance_minor;
            $pending->save();

            $this->writeAudit($pending, 'commission_released', $before, [
                'balance_minor' => $locked->balance_minor,
                'reserved_balance_minor' => $locked->reserved_balance_minor,
            ], $actorIp);
        });
    }

    /**
     * Finalize commission: take finalized_minor from the reserved hold; any remainder returns to spendable balance.
     */
    public function finalizeCommissionMinor(Wallet $wallet, Trip $trip, int $finalizedMinor, ?string $actorIp = null): void
    {
        if ($finalizedMinor < 0) {
            throw new DomainException('Commission finalize amount cannot be negative.');
        }

        DB::transaction(function () use ($wallet, $trip, $finalizedMinor, $actorIp): void {
            /** @var Wallet $locked */
            $locked = Wallet::query()->whereKey($wallet->id)->lockForUpdate()->firstOrFail();

            $pending = WalletTransaction::query()
                ->where('wallet_id', $locked->id)
                ->where('reference_type', 'trip')
                ->where('reference_id', $trip->id)
                ->where('type', WalletTransactionType::Commission)
                ->where('status', WalletTransactionStatus::Pending)
                ->lockForUpdate()
                ->first();

            if (! $pending) {
                throw new DomainException('No pending commission reservation for this trip.');
            }

            $reserved = $pending->amount_minor;
            if ($finalizedMinor > $reserved) {
                throw new DomainException('Finalize amount exceeds reserved commission.');
            }

            $before = [
                'balance_minor' => $locked->balance_minor,
                'reserved_balance_minor' => $locked->reserved_balance_minor,
            ];

            $remainder = $reserved - $finalizedMinor;
            $locked->reserved_balance_minor -= $reserved;
            $locked->balance_minor += $remainder;
            $locked->save();

            $pending->amount_minor = $reserved;
            $pending->status = WalletTransactionStatus::Completed;
            $pending->balance_after_minor = $locked->balance_minor;
            $pending->reserved_after_minor = $locked->reserved_balance_minor;
            $pending->metadata = array_merge($pending->metadata ?? [], [
                'phase' => 'finalized',
                'finalized_minor' => $finalizedMinor,
                'returned_to_balance_minor' => $remainder,
            ]);
            $pending->save();

            $this->writeAudit($pending, 'commission_finalized', $before, [
                'balance_minor' => $locked->balance_minor,
                'reserved_balance_minor' => $locked->reserved_balance_minor,
            ], $actorIp);
        });
    }

    public function creditTopupMinor(Wallet $wallet, int $amountMinor, ?string $actorIp = null): WalletTransaction
    {
        if ($amountMinor <= 0) {
            throw new DomainException('Top-up amount must be positive.');
        }

        return DB::transaction(function () use ($wallet, $amountMinor, $actorIp): WalletTransaction {
            /** @var Wallet $locked */
            $locked = Wallet::query()->whereKey($wallet->id)->lockForUpdate()->firstOrFail();
            $before = [
                'balance_minor' => $locked->balance_minor,
                'reserved_balance_minor' => $locked->reserved_balance_minor,
            ];
            $locked->balance_minor += $amountMinor;
            $locked->save();

            $txn = WalletTransaction::query()->create([
                'wallet_id' => $locked->id,
                'type' => WalletTransactionType::Topup,
                'status' => WalletTransactionStatus::Completed,
                'amount_minor' => $amountMinor,
                'direction' => WalletTransactionDirection::Credit,
                'balance_after_minor' => $locked->balance_minor,
                'reserved_after_minor' => $locked->reserved_balance_minor,
                'metadata' => ['source' => 'api_topup'],
            ]);

            $this->writeAudit($txn, 'topup', $before, [
                'balance_minor' => $locked->balance_minor,
                'reserved_balance_minor' => $locked->reserved_balance_minor,
            ], $actorIp);

            return $txn;
        });
    }

    /**
     * @param  array<string, int>  $before
     * @param  array<string, int>  $after
     */
    private function writeAudit(
        WalletTransaction $txn,
        string $action,
        array $before,
        array $after,
        ?string $actorIp,
    ): void {
        WalletTransactionAudit::query()->create([
            'wallet_transaction_id' => $txn->id,
            'action' => $action,
            'snapshot_before' => $before,
            'snapshot_after' => $after,
            'actor_user_id' => auth()->id(),
            'ip_address' => $actorIp,
            'created_at' => now(),
        ]);
    }
}
