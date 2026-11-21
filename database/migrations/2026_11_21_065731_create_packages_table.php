<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->id();

            $table->foreignId('site_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');
            $table->text('description')->nullable();

            $table->decimal('price', 12, 2);
            $table->string('currency', 3)->default('TZS');

            $table->unsignedInteger('duration_minutes');

            $table->unsignedInteger('download_speed_kbps');
            $table->unsignedInteger('upload_speed_kbps');

            $table->unsignedBigInteger('data_limit_bytes')->nullable();

            $table->unsignedInteger('device_limit')->default(1);

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['site_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('packages');
    }
};
