<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('network_logs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('site_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('device_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->enum('level', [
                'info',
                'warning',
                'error',
            ])->default('info');

            $table->string('event', 100);
            $table->text('message')->nullable();
            $table->json('context')->nullable();

            $table->timestamp('occurred_at')->useCurrent();

            $table->timestamps();

            $table->index(['site_id', 'level', 'occurred_at']);
            $table->index(['device_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('network_logs');
    }
};