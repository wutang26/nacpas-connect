
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('network_commands', function (Blueprint $table) {
            $table->unique(
                ['wifi_session_id', 'command_type'],
                'network_commands_session_type_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('network_commands', function (Blueprint $table) {
            $table->dropUnique(
                'network_commands_session_type_unique'
            );
        });
    }
};
