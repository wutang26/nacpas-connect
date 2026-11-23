<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->enum('provider', [
                'mpesa',
                'tigopesa',
                'airtelmoney',
                'halopesa',
                'fastlipa',
                'other',
            ])->default('other')->change();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->enum('provider', [
                'mpesa',
                'tigopesa',
                'airtelmoney',
                'halopesa',
                'other',
            ])->default('other')->change();
        });
    }
};
