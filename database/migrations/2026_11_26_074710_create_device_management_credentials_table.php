<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_management_credentials', function (Blueprint $table) {
            $table->id();

            $table->foreignId('connection_id')
                ->constrained('device_management_connections')
                ->cascadeOnDelete();

            $table->foreignId('device_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->string('credential_type', 60);
            $table->string('label')->nullable();
            $table->string('username')->nullable();
            $table->text('secret')->nullable();
            $table->text('endpoint')->nullable();
            $table->json('metadata')->nullable();

            $table->string('status', 30)->default('active');
            $table->timestamp('last_used_at')->nullable();

            $table->timestamps();

            $table->index(['connection_id', 'status']);
            $table->index(['device_id', 'credential_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_management_credentials');
    }
};
