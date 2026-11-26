<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_management_capabilities', function (Blueprint $table) {
            $table->id();

            $table->foreignId('device_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('capability_key', 100);
            $table->string('source', 50);
            $table->boolean('is_supported')->default(false);
            $table->boolean('is_enabled')->default(false);

            $table->json('details')->nullable();
            $table->timestamp('verified_at')->nullable();

            $table->timestamps();

            $table->unique(
                ['device_id', 'capability_key', 'source'],
                'device_capability_source_unique'
            );

            $table->index(['device_id', 'is_supported']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_management_capabilities');
    }
};
