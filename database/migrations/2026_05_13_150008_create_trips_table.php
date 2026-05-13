<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trips', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('driver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('vehicle_id')->nullable()->constrained('vehicles')->nullOnDelete();
            $table->foreignUuid('vehicle_type_id')->constrained('vehicle_types')->restrictOnDelete();
            $table->json('pickup_location');
            $table->json('destination_location');
            $table->text('cargo_description')->nullable();
            $table->string('cargo_photo')->nullable();
            $table->unsignedBigInteger('estimated_price_minor');
            $table->unsignedBigInteger('final_price_minor')->nullable();
            $table->string('trip_status', 32);
            $table->string('payment_status', 32);
            $table->unsignedBigInteger('commission_amount_minor')->default(0);
            $table->string('commission_state', 32)->default('none');
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['trip_status', 'created_at']);
            $table->index(['customer_id', 'trip_status']);
            $table->index(['driver_id', 'trip_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trips');
    }
};
