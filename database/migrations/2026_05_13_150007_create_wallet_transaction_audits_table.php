<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_transaction_audits', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('wallet_transaction_id')->constrained('wallet_transactions')->cascadeOnDelete();
            $table->string('action', 64);
            $table->json('snapshot_before')->nullable();
            $table->json('snapshot_after')->nullable();
            $table->foreignUuid('actor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transaction_audits');
    }
};
