<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Modules\Users\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Queued FCM delivery; extend with Kreait Messaging when device tokens are registered.
 */
final class SendFcmNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $userId,
        public string $title,
        public string $body,
        /** @var array<string, mixed> */
        public array $data = [],
    ) {}

    public function handle(): void
    {
        $user = User::query()->find($this->userId);
        if (! $user) {
            return;
        }

        Log::info('notifications.fcm_stub', [
            'user_id' => $user->id,
            'title' => $this->title,
            'body' => $this->body,
            'data' => $this->data,
        ]);
    }
}
