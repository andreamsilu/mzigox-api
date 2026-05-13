<?php

declare(strict_types=1);

namespace App\Modules\Admin\Http\Controllers;

use App\Enums\WalletTransactionStatus;
use App\Enums\WalletTransactionType;
use App\Helpers\ApiResponse;
use App\Modules\Wallets\Models\WalletTransaction;
use Illuminate\Http\JsonResponse;

final class AdminReportController
{
    public function commission(): JsonResponse
    {
        $rows = WalletTransaction::query()
            ->where('type', WalletTransactionType::Commission)
            ->where('status', WalletTransactionStatus::Completed)
            ->orderBy('created_at')
            ->get()
            ->groupBy(fn (WalletTransaction $t) => $t->created_at?->format('Y-m') ?? 'unknown')
            ->map(fn ($group, $month) => [
                'month' => $month,
                'total_minor' => $group->sum('amount_minor'),
                'txn_count' => $group->count(),
            ])
            ->values();

        return ApiResponse::success($rows->all());
    }

    public function disputes(): JsonResponse
    {
        return ApiResponse::success([
            'message' => 'Dispute APIs are reserved for mediation workflows; attach ticketing integration here.',
            'items' => [],
        ]);
    }
}
