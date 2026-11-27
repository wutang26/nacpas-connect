<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voucher_redemptions', function (Blueprint $table) {
            $table->id();

            // A voucher can only be redeemed once.
            $table->foreignId('voucher_id')
                ->unique()
                ->constrained()->restrictOnDelete();

            $table->foreignId('voucher_batch_id')
                ->constrained()->restrictOnDelete();

            $table->foreignId('site_id')
                ->constrained()->restrictOnDelete();

            $table->foreignId('package_id')
                ->constrained()->restrictOnDelete();

            $table->foreignId('voucher_agent_id')
                ->nullable()
                ->constrained()->restrictOnDelete();

            $table->foreignId('customer_id')
                ->constrained()->restrictOnDelete();

            // One subscription can only have one voucher redemption.
            $table->foreignId('subscription_id')
                ->unique()
                ->constrained()->restrictOnDelete();

            $table->foreignId('wifi_session_id')
                ->nullable()
                ->constrained()->nullOnDelete();

            $table->string('client_mac', 50)->nullable();
            $table->string('client_ip', 45)->nullable();

            $table->timestamp('redeemed_at');

            $table->timestamps();

            $table->index(['site_id', 'redeemed_at']);
            $table->index(['voucher_agent_id', 'redeemed_at']);
            $table->index(['customer_id', 'redeemed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voucher_redemptions');
    }
};
