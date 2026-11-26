<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_management_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organization_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('site_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('device_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('connection_id')
                ->nullable()
                ->constrained('device_management_connections')
                ->nullOnDelete();

            $table->foreignId('actor_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('level', 20)->default('info');
            $table->string('event', 100);
            $table->text('message')->nullable();
            $table->json('context')->nullable();
            $table->timestamp('occurred_at');

            $table->timestamps();

            $table->index(['organization_id', 'occurred_at']);
            $table->index(['site_id', 'level', 'occurred_at']);
            $table->index(['device_id', 'occurred_at']);
            $table->index(['connection_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_management_logs');
    }
};
