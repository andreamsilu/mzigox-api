<?php

declare(strict_types=1);

namespace App\Services;

use App\Modules\Wallets\Models\Wallet;

/**
 * Central place for balance semantics (available vs reserved).
 */
final class WalletBalanceService
{
    public function availableMinor(Wallet $wallet): int
    {
        return max(0, $wallet->balance_minor - $wallet->reserved_balance_minor);
    }

    public function totalMinor(Wallet $wallet): int
    {
        return $wallet->balance_minor;
    }
}
