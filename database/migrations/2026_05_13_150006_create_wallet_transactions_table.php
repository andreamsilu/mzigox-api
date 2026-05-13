<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('wallet_id')->constrained('wallets')->cascadeOnDelete();
            $table->string('type', 32);
            $table->string('status', 32);
            $table->unsignedBigInteger('amount_minor');
            $table->string('direction', 8);
            $table->unsignedBigInteger('balance_after_minor');
            $table->unsignedBigInteger('reserved_after_minor');
            $table->nullableUuidMorphs('reference');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['wallet_id', 'type']);
            $table->index(['wallet_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
    }
};
