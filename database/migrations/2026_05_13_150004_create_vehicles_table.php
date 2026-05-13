<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vehicles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('vehicle_type_id')->constrained('vehicle_types')->restrictOnDelete();
            $table->foreignUuid('owner_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('driver_id')->nullable()->constrained('drivers')->nullOnDelete();
            $table->string('plate_number', 32);
            $table->unsignedInteger('capacity_kg')->nullable();
            $table->string('status', 32);
            $table->timestamps();
            $table->softDeletes();
            $table->unique('plate_number');
            $table->index('vehicle_type_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vehicles');
    }
};
