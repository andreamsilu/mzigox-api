<?php

declare(strict_types=1);

namespace App\Enums;

enum WalletTransactionDirection: string
{
    case Credit = 'credit';
    case Debit = 'debit';
}
