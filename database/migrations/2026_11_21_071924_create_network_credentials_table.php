<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('network_credentials', function (Blueprint $table) {
            $table->id();

            $table->foreignId('site_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('device_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->enum('provider', [
                'omada',
                'mikrotik',
                'radius',
                'other',
            ]);

            $table->string('credential_type', 50);
            $table->string('username')->nullable();
            $table->text('secret')->nullable();
            $table->text('endpoint')->nullable();
            $table->json('metadata')->nullable();

            $table->enum('status', [
                'active',
                'inactive',
            ])->default('active');

            $table->timestamps();

            $table->index(['site_id', 'provider', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('network_credentials');
    }
};