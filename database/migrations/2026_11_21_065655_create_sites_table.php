<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sites', function (Blueprint $table) {
            $table->id();

            $table->foreignId('organization_id')
            ->constrained('organizations')
            ->cascadeOnDelete();
            
            // Site owner / creator
            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnDelete();

            // Basic information
            $table->string('name');
            $table->string('code', 50)->unique();
            $table->string('slug')->unique();
            $table->text('description')->nullable();

            // Location information
            $table->text('address')->nullable();
            $table->string('region')->nullable();
            $table->string('district')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // Timezone
            $table->string('timezone')->default('Africa/Dar_es_Salaam');

            // Site status
            $table->enum('status', [
                'active',
                'inactive',
                'suspended',
            ])->default('active');

            $table->timestamps();

            $table->index(['created_by', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sites');
    }
};
