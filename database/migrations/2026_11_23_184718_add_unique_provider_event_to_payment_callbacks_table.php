<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payment_callbacks', function (Blueprint $table) {
            $table->unique(
                ['provider', 'event_id'],
                'payment_callbacks_provider_event_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('payment_callbacks', function (Blueprint $table) {
            $table->dropUnique(
                'payment_callbacks_provider_event_unique'
            );
        });
    }
};