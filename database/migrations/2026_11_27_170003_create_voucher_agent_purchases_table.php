<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voucher_agent_purchases', function (Blueprint $table) {
            $table->id();

            $table->foreignId('voucher_agent_id')
                ->constrained()->restrictOnDelete();

            $table->foreignId('voucher_batch_id')
                ->constrained()->restrictOnDelete();

            $table->foreignId('site_id')
                ->constrained()->restrictOnDelete();

            $table->string('reference', 80)->unique();

            $table->unsignedInteger('quantity');

            $table->decimal('wholesale_unit_price', 12, 2);
            $table->decimal('retail_unit_price', 12, 2);
            $table->decimal('total_amount', 12, 2);

            $table->string('currency', 3)->default('TZS');

            $table->enum('status', [
                'pending',
                'paid',
                'cancelled',
            ])->default('pending');

            $table->string('payment_method', 50)->nullable();
            $table->string('payment_reference', 120)->nullable();

            $table->foreignId('confirmed_by')
                ->nullable()
                ->constrained('users')
                ->restrictOnDelete();

            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['voucher_agent_id', 'status']);
            $table->index(['site_id', 'status']);
            $table->index(['voucher_batch_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voucher_agent_purchases');
    }
};
