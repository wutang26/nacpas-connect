<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_management_commands', function (Blueprint $table) {
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

            $table->foreignId('connection_id')
                ->nullable()
                ->constrained('device_management_connections')
                ->nullOnDelete();

            $table->foreignId('requested_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->uuid('command_uuid')->unique();
            $table->string('command_type', 100);
            $table->json('payload')->nullable();
            $table->json('result')->nullable();

            $table->string('status', 30)->default('queued');
            $table->unsignedSmallInteger('attempts')->default(0);

            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->text('error_message')->nullable();

            $table->timestamps();

            $table->index(['organization_id', 'status']);
            $table->index(['site_id', 'status']);
            $table->index(['device_id', 'created_at']);
            $table->index(['connection_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_management_commands');
    }
};
