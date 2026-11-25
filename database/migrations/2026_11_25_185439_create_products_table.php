<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            $table->string('sku', 80)->unique();
            $table->string('name');
            $table->string('category', 100);
            $table->string('brand')->nullable();
            $table->string('model')->nullable();

            $table->text('description')->nullable();
            $table->longText('specifications')->nullable();

            $table->decimal('default_purchase_price', 12, 2)->nullable();
            $table->decimal('default_selling_price', 12, 2)->nullable();

            $table->string('currency', 3)->default('TZS');
            $table->string('unit_of_measure', 30)->default('piece');

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['category', 'is_active']);
            $table->index(['name', 'brand']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
