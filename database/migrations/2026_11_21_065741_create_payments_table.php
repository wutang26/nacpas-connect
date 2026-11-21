<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('subscription_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('customer_id')
                ->constrained()
                ->restrictOnDelete();

            $table->enum('provider', [
                'mpesa',
                'tigopesa',
                'airtelmoney',
                'halopesa',
                'other',
            ]);

            $table->string('provider_reference')->nullable();
            $table->string('internal_reference')->unique();

            $table->string('phone', 30);
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('TZS');

            $table->enum('status', [
                'pending',
                'successful',
                'failed',
                'refunded',
            ])->default('pending');

            $table->timestamp('paid_at')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index(['subscription_id', 'status']);
            $table->index('provider_reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};