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
        Schema::create('whatsapp_flows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('flow_id')->nullable()->unique(); // Meta's ID
            $table->string('name');
            $table->string('status')->default('DRAFT'); // DRAFT, PUBLISHED, DEPRECATED
            $table->json('design_data')->nullable(); // Our builder data
            $table->json('flow_json')->nullable(); // JSON sent to Meta
            $table->timestamps();
        });

        Schema::create('whatsapp_flow_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('whatsapp_flow_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained()->cascadeOnDelete();
            $table->json('data'); // The payload from FLOW_RESPONSE
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('whatsapp_flow_responses');
        Schema::dropIfExists('whatsapp_flows');
    }
};
