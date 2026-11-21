<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('site_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('customer_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('package_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('reference')->unique();

            // Package purchase snapshot
            $table->string('package_name');
            $table->decimal('amount', 12, 2);
            $table->unsignedInteger('duration_minutes');

            $table->enum('status', [
                'pending',
                'active',
                'expired',
                'cancelled',
            ])->default('pending');

            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('activated_at')->nullable();

            $table->timestamps();

            $table->index(['site_id', 'status']);
            $table->index(['customer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};

