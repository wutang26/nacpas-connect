<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wifi_portal_contexts', function (Blueprint $table) {
            $table->id();

            // Unique reference passed from the portal page to checkout.
            $table->uuid('context_token')->unique();

            // Bind the context to the browser's Laravel session.
            $table->char('session_hash', 64);

            $table->foreignId('site_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('payment_id')
                ->nullable()
                ->constrained('payments')
                ->nullOnDelete();

            $table->foreignId('subscription_id')
                ->nullable()
                ->constrained('subscriptions')
                ->nullOnDelete();

            $table->foreignId('wifi_session_id')
                ->nullable()
                ->constrained('wifi_sessions')
                ->nullOnDelete();

            // Information supplied by the Omada External Portal redirect.
            $table->string('client_mac', 50)->nullable();
            $table->string('client_ip', 45)->nullable();
            $table->string('ap_mac', 50)->nullable();
            $table->string('ssid_name')->nullable();
            $table->string('radio_id', 50)->nullable();

            // Omada controller site identifier, not NACPASI site_id.
            $table->string('controller_site_id')->nullable();

            // Original redirect URL supplied by the controller.
            $table->text('redirect_url')->nullable();

            // Omada timestamp, normally milliseconds.
            $table->unsignedBigInteger('omada_timestamp')->nullable();

            $table->enum('context_status', [
                'captured',
                'attached',
                'consumed',
                'expired',
            ])->default('captured');

            $table->enum('authorization_status', [
                'not_started',
                'queued',
                'authorizing',
                'authorized',
                'failed',
            ])->default('not_started');

            $table->unsignedSmallInteger('authorization_attempts')
                ->default(0);

            $table->timestamp('authorized_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            $table->text('last_error')->nullable();

            $table->timestamps();

            $table->index(['site_id', 'context_status']);
            $table->index(['site_id', 'authorization_status']);
            $table->index('payment_id');
            $table->index('subscription_id');
            $table->index('wifi_session_id');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wifi_portal_contexts');
    }
};
