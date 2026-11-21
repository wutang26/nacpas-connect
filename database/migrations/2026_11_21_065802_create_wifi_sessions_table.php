<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wifi_sessions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('site_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('subscription_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('customer_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('device_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('client_mac', 50);
            $table->string('client_ip', 45)->nullable();
            $table->string('external_session_id')->nullable();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('ended_at')->nullable();

            $table->unsignedBigInteger('bytes_in')->default(0);
            $table->unsignedBigInteger('bytes_out')->default(0);

            $table->enum('status', [
                'pending',
                'active',
                'expired',
                'disconnected',
            ])->default('pending');

            $table->timestamps();

            $table->index(['site_id', 'status']);
            $table->index(['customer_id', 'status']);
            $table->index('external_session_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wifi_sessions');
    }
};
