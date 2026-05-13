<?php

declare(strict_types=1);

namespace App\Modules\Admin\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Modules\Wallets\Http\Resources\WalletResource;
use App\Modules\Wallets\Models\Wallet;
use Illuminate\Http\JsonResponse;

final class AdminWalletController
{
    public function index(): JsonResponse
    {
        $wallets = Wallet::query()
            ->with('user:id,full_name,phone,role')
            ->orderByDesc('balance_minor')
            ->limit(100)
            ->get();

        $data = $wallets->map(fn (Wallet $w) => array_merge(
            (new WalletResource($w))->resolve(),
            [
                'user' => [
                    'id' => $w->user?->id,
                    'full_name' => $w->user?->full_name,
                    'phone' => $w->user?->phone,
                    'role' => $w->user?->role?->value,
                ],
            ]
        ));

        return ApiResponse::success($data->values()->all());
    }
}
