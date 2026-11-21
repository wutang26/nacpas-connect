<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_callbacks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('payment_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('provider', 50);
            $table->string('event_id')->nullable();
            $table->string('reference')->nullable();

            $table->json('payload');

            $table->boolean('signature_verified')->default(false);

            $table->enum('processing_status', [
                'pending',
                'processed',
                'failed',
            ])->default('pending');

            $table->timestamp('processed_at')->nullable();

            $table->timestamps();

            $table->index(['provider', 'reference']);
            $table->index(['processing_status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_callbacks');
    }
};
