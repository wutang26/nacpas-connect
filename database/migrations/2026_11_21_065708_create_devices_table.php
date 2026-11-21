<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('site_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');
            $table->string('vendor', 100);
            $table->string('model')->nullable();

            $table->enum('device_type', [
                'access_point',
                'gateway',
                'router',
                'switch',
                'other',
            ])->default('access_point');

            $table->string('mac_address', 50)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('serial_number')->nullable();
            $table->string('controller_device_id')->nullable();
            $table->string('firmware_version')->nullable();

            $table->enum('status', [
                'online',
                'offline',
                'disabled',
            ])->default('offline');

            $table->timestamp('last_seen_at')->nullable();

            $table->timestamps();

            $table->index(['site_id', 'status']);
            $table->unique(['site_id', 'mac_address']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};



