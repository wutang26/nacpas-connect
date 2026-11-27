<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voucher_batches', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organization_id')
                ->constrained()->restrictOnDelete();

            $table->foreignId('site_id')
                ->constrained()->restrictOnDelete();

            $table->foreignId('package_id')
                ->constrained()->restrictOnDelete();

            $table->foreignId('created_by')
                ->constrained('users')->restrictOnDelete();

            $table->string('batch_number', 60)->unique();

            $table->unsignedInteger('quantity');
            $table->unsignedInteger('generated_count')->default(0);

            $table->decimal('wholesale_unit_price', 12, 2);
            $table->decimal('retail_unit_price', 12, 2);
            $table->string('currency', 3)->default('TZS');

            $table->enum('status', [
                'active',
                'closed',
            ])->default('active');

            $table->timestamp('expires_at')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['organization_id', 'site_id']);
            $table->index(['site_id', 'status']);
            $table->index(['package_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voucher_batches');
    }
};
