<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saas_payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organization_subscription_id')
                ->constrained('organization_subscriptions')
                ->restrictOnDelete();

            $table->string('provider', 50);

            $table->string('internal_reference')->unique();
            $table->string('provider_reference')->nullable();

            $table->string('phone_number', 30)->nullable();

            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('TZS');

            $table->enum('status', [
                'pending',
                'successful',
                'failed',
                'cancelled',
                'refunded',
            ])->default('pending');

            $table->dateTime('paid_at')->nullable();

            $table->json('metadata')->nullable();

            $table->timestamps();

            $table->index([
                'organization_subscription_id',
                'status',
            ]);

            $table->index('provider_reference');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saas_payments');
    }
};
