
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wifi_networks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('site_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');
            $table->string('ssid', 32);

            $table->enum('security_type', [
                'open',
                'wpa2',
            ])->default('open');

            // Password itahifadhiwa ikiwa encrypted na Eloquent.
            $table->text('password')->nullable();

            // Itatumika baadaye kuunganisha na Omada WLAN.
            $table->string('controller_wlan_id')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(['site_id', 'ssid']);
            $table->index(['site_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wifi_networks');
    }
};
