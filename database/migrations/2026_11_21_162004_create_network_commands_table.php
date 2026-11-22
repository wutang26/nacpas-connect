<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('network_commands', function (Blueprint $table) {
            $table->id();

            $table->foreignId('network_integration_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('site_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('device_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('wifi_session_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->uuid('command_uuid')->unique();

            // Examples: authorize_client, disconnect_client
            $table->string('command_type', 100);

            $table->string('target_mac', 50)->nullable();

            $table->json('payload')->nullable();
            $table->json('response')->nullable();

            $table->enum('status', [
                'pending',
                'processing',
                'succeeded',
                'failed',
            ])->default('pending');

            $table->unsignedSmallInteger('attempts')->default(0);

            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamp('executed_at')->nullable();

            $table->text('error_message')->nullable();

            $table->timestamps();

            $table->index(['site_id', 'status']);
            $table->index(['network_integration_id', 'status']);
            $table->index(['wifi_session_id', 'command_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('network_commands');
    }
};
