<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_subscriptions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organization_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('saas_plan_id')
                ->constrained('saas_plans')
                ->restrictOnDelete();

            $table->enum('billing_cycle', [
                'monthly',
                'annual',
            ])->default('monthly');

            $table->enum('status', [
                'trialing',
                'active',
                'past_due',
                'cancelled',
                'expired',
            ])->default('trialing');

            $table->decimal('amount', 12, 2)->default(0);
            $table->string('currency', 3)->default('TZS');

            $table->dateTime('current_period_start')->nullable();
            $table->dateTime('current_period_end')->nullable();

            $table->dateTime('trial_ends_at')->nullable();
            $table->dateTime('grace_ends_at')->nullable();

            $table->boolean('cancel_at_period_end')->default(false);
            $table->dateTime('cancelled_at')->nullable();

            $table->timestamps();

            $table->index(['organization_id', 'status']);
            $table->index('current_period_end');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_subscriptions');
    }
};
