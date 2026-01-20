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
        Schema::create('campaign_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->string('template_name');
            $table->string('template_language')->default('en_US');
            $table->json('template_variables')->nullable();
            $table->json('header_params')->nullable();
            $table->json('footer_params')->nullable();
            $table->unsignedInteger('audience_count')->default(0);
            $table->json('meta')->nullable(); // For any extra context
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaign_snapshots');
    }
};
