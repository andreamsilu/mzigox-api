<?php

declare(strict_types=1);

namespace App\Modules\Wallets\Http\Controllers;

use App\Helpers\ApiResponse;
use App\Modules\Wallets\Http\Requests\WalletTopupRequest;
use App\Modules\Wallets\Http\Resources\WalletResource;
use App\Services\WalletService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class WalletController
{
    public function __construct(
        private readonly WalletService $walletService,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $wallet = $this->walletService->getOrCreateWallet($request->user());

        return ApiResponse::success((new WalletResource($wallet))->resolve());
    }

    public function topup(WalletTopupRequest $request): JsonResponse
    {
        $wallet = $this->walletService->getOrCreateWallet($request->user());
        $this->walletService->creditTopupMinor($wallet, (int) $request->validated('amount_minor'), $request->ip());

        return ApiResponse::success((new WalletResource($wallet->fresh()))->resolve(), 'Wallet topped up.');
    }
}
