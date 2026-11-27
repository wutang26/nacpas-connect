
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Add unknown status for uncertain Omada authorization outcomes.
     */
    public function up(): void
    {
        DB::statement("
            ALTER TABLE wifi_portal_contexts
            MODIFY authorization_status
            ENUM(
                'not_started',
                'queued',
                'authorizing',
                'authorized',
                'failed',
                'unknown'
            ) NOT NULL DEFAULT 'not_started'
        ");
    }

    /**
     * Revert the authorization status enum.
     */
    public function down(): void
    {
        DB::table('wifi_portal_contexts')
            ->where('authorization_status', 'unknown')
            ->update([
                'authorization_status' => 'failed',
            ]);

        DB::statement("
            ALTER TABLE wifi_portal_contexts
            MODIFY authorization_status
            ENUM(
                'not_started',
                'queued',
                'authorizing',
                'authorized',
                'failed'
            ) NOT NULL DEFAULT 'not_started'
        ");
    }
};
