<?php


use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organizations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('owner_user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->string('name');
            $table->string('slug')->unique();
            $table->string('code')->unique();

            $table->string('business_email')->nullable();
            $table->string('business_phone', 30)->nullable();
            $table->string('billing_email')->nullable();

            $table->string('address')->nullable();
            $table->string('region')->nullable();
            $table->string('district')->nullable();

            $table->string('timezone')
                ->default('Africa/Dar_es_Salaam');

            $table->enum('status', [
                'active',
                'inactive',
                'suspended',
            ])->default('active');

            $table->timestamps();

            $table->index(['owner_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organizations');
    }
};
