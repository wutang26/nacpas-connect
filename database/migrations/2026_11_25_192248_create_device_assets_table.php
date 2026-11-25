
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_assets', function (Blueprint $table) {
            $table->id();

            // NACPASI's unique identity for each physical device
            $table->string('asset_tag', 50)->nullable()->unique();

            // Product catalogue reference
            $table->foreignId('product_id')
                ->constrained('products')
                ->restrictOnDelete();

            // Physical device identification
            $table->string('serial_number')->nullable()->unique();
            $table->string('mac_address', 50)->nullable()->unique();

            // Asset lifecycle
            $table->enum('status', [
                'in_stock',
                'reserved',
                'sold',
                'installed',
                'maintenance',
                'damaged',
                'retired',
            ])->default('in_stock');

            $table->enum('condition', [
                'new',
                'used',
                'refurbished',
                'damaged',
            ])->default('new');

            // Purchase information
            $table->date('purchase_date')->nullable();
            $table->decimal('purchase_cost', 14, 2)->nullable();
            $table->string('currency', 3)->default('TZS');
            $table->date('warranty_expires_at')->nullable();

            // Business ownership after sale
            $table->foreignId('organization_id')
                ->nullable()
                ->constrained('organizations')
                ->restrictOnDelete();

            // Physical installation location
            $table->foreignId('site_id')
                ->nullable()
                ->constrained('sites')
                ->restrictOnDelete();

            // Link to the network-managed device after installation
            $table->foreignId('device_id')
                ->nullable()
                ->unique()
                ->constrained('devices')
                ->nullOnDelete();

            // Lifecycle dates
            $table->timestamp('sold_at')->nullable();
            $table->timestamp('installed_at')->nullable();

            $table->text('notes')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['product_id', 'status']);
            $table->index(['organization_id', 'status']);
            $table->index(['site_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_assets');
    }
};
