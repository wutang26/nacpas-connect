<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            ALTER TABLE network_commands
            MODIFY status ENUM(
                'pending',
                'processing',
                'succeeded',
                'failed',
                'unknown'
            ) NOT NULL DEFAULT 'pending'
        ");
    }

    public function down(): void
    {
        if (DB::table('network_commands')
            ->where('status', 'unknown')
            ->exists()) {
            throw new RuntimeException(
                'Cannot rollback: network_commands contains unknown commands.'
            );
        }

        DB::statement("
            ALTER TABLE network_commands
            MODIFY status ENUM(
                'pending',
                'processing',
                'succeeded',
                'failed'
            ) NOT NULL DEFAULT 'pending'
        ");
    }
};
