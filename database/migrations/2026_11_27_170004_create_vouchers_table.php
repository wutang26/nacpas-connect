<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vouchers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('voucher_batch_id')
                ->constrained()->restrictOnDelete();

            $table->foreignId('site_id')
                ->constrained()->restrictOnDelete();

            $table->foreignId('package_id')
                ->constrained()->restrictOnDelete();

            $table->foreignId('voucher_agent_purchase_id')
                ->nullable()
                ->constrained('voucher_agent_purchases')
                ->restrictOnDelete();

            $table->foreignId('voucher_agent_id')
                ->nullable()
                ->constrained('voucher_agents')
                ->restrictOnDelete();

            // SHA-256 hash is used for secure voucher lookup.
            $table->char('code_hash', 64)->unique();

            // Encrypted code allows secure delivery to the assigned agent.
            $table->text('encrypted_code');

            // Used for inventory identification without exposing the code.
            $table->char('code_suffix', 4);

            $table->decimal('retail_price', 12, 2);
            $table->string('currency', 3)->default('TZS');

            $table->enum('status', [
                'available',
                'assigned',
                'redeemed',
                'void',
            ])->default('available');

            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('redeemed_at')->nullable();

            $table->timestamps();

            $table->index(['site_id', 'status']);
            $table->index(['voucher_batch_id', 'status']);
            $table->index(['voucher_agent_id', 'status']);
            $table->index('voucher_agent_purchase_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vouchers');
    }
};
