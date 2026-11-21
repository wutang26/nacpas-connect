<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qr_codes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('site_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('code')->unique();
            $table->string('label')->nullable();
            $table->text('target_url');

            $table->enum('status', [
                'active',
                'inactive',
            ])->default('active');

            $table->timestamp('expires_at')->nullable();

            $table->timestamps();

            $table->index(['site_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qr_codes');
    }
};