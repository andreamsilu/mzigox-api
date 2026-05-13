<?php

declare(strict_types=1);

namespace App\Modules\Wallets\Http\Resources;

use App\Modules\Wallets\Models\Wallet;
use App\Services\WalletBalanceService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Wallet */
class WalletResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $balanceService = app(WalletBalanceService::class);

        return [
            'id' => $this->id,
            'currency' => $this->currency,
            'balance_minor' => $this->balance_minor,
            'reserved_balance_minor' => $this->reserved_balance_minor,
            'available_balance_minor' => $balanceService->availableMinor($this->resource),
        ];
    }
}
