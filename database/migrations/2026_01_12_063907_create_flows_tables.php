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
        Schema::create('flows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('trigger_keyword')->nullable();
            $table->json('nodes')->nullable();
            $table->json('edges')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamps();
        });

        Schema::create('flow_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('flow_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->string('current_step_id')->nullable();
            $table->json('state')->nullable();
            $table->string('status')->default('active'); // active, completed, expired
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('flow_sessions');
        Schema::dropIfExists('flows');
    }
};
