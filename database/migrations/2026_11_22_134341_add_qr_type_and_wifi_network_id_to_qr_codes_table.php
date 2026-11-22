
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('qr_codes', function (Blueprint $table) {
            $table->enum('qr_type', [
                'portal',
                'wifi',
            ])->default('portal')->after('site_id');

            $table->foreignId('wifi_network_id')
                ->nullable()
                ->after('qr_type')
                ->constrained('wifi_networks')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('qr_codes', function (Blueprint $table) {
            $table->dropForeign(['wifi_network_id']);
            $table->dropColumn([
                'qr_type',
                'wifi_network_id',
            ]);
        });
    }
};
