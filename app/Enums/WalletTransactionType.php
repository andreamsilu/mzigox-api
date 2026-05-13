<?php

declare(strict_types=1);

namespace App\Enums;

enum WalletTransactionType: string
{
    case Topup = 'topup';
    case Withdrawal = 'withdrawal';
    case TripPayment = 'trip_payment';
    case Commission = 'commission';
    case Refund = 'refund';
    case Bonus = 'bonus';
}
