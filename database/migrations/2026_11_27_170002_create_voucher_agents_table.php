<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voucher_agents', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained()->restrictOnDelete();

            $table->foreignId('site_id')
                ->constrained()->restrictOnDelete();

            $table->enum('status', [
                'active',
                'suspended',
            ])->default('active');

            $table->string('agent_code', 40)->unique();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique('user_id', 'voucher_agents_user_unique');

            $table->index(['site_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voucher_agents');
    }
};
