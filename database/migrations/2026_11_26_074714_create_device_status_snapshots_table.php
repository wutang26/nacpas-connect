<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_status_snapshots', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organization_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('site_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('device_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('status', 30);
            $table->string('ip_address', 45)->nullable();
            $table->string('firmware_version')->nullable();

            $table->unsignedInteger('uptime_seconds')->nullable();
            $table->unsignedInteger('connected_clients')->nullable();

            $table->decimal('cpu_usage', 5, 2)->nullable();
            $table->decimal('memory_usage', 5, 2)->nullable();

            $table->json('metrics')->nullable();
            $table->timestamp('observed_at');

            $table->timestamps();

            $table->index(['device_id', 'observed_at']);
            $table->index(['organization_id', 'observed_at']);
            $table->index(['site_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_status_snapshots');
    }
};
