<?php

declare(strict_types=1);

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * SMS fallback pipeline hook; swap driver for Twilio/Africa's Talking in production.
 */
final class SendSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $phone,
        public string $message,
    ) {}

    public function handle(): void
    {
        Log::info('notifications.sms_stub', ['phone' => $this->phone, 'message' => $this->message]);
    }
}
