<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saas_plans', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();

            $table->decimal('price_monthly', 12, 2)->default(0);
            $table->decimal('price_annual', 12, 2)->default(0);

            $table->string('currency', 3)->default('TZS');

            $table->unsignedInteger('max_sites')->default(1);
            $table->unsignedInteger('max_users')->default(2);
            $table->unsignedInteger('max_devices')->default(2);

            $table->json('features')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saas_plans');
    }
};
