<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('smtp_configs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('host');
            $table->string('port');
            $table->string('username');
            $table->text('password'); // Encrypted
            $table->string('encryption')->nullable();
            $table->string('from_address');
            $table->string('from_name');
            $table->integer('priority')->default(100);
            $table->json('use_case')->nullable(); // Array of supported use cases
            $table->boolean('is_active')->default(true);
            $table->string('health_status')->default('healthy'); // healthy, degraded, failing
            $table->timestamp('last_checked_at')->nullable();
            $table->integer('failure_count')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('smtp_configs');
    }
};
