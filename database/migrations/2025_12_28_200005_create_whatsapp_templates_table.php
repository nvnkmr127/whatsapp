<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('whatsapp_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();

            $table->string('whatsapp_template_id')->nullable(); // Meta's ID
            $table->string('name');
            $table->string('language');

            $table->enum('category', ['UTILITY', 'MARKETING', 'AUTHENTICATION']);
            $table->enum('status', ['APPROVED', 'PENDING', 'REJECTED', 'PAUSED', 'DISABLED'])->default('PENDING');

            $table->json('components'); // The header, body, footer structure

            $table->timestamps();

            // Unique constraint: A name + language must be unique per team account (usually per WABA, but here per team)
            $table->unique(['team_id', 'name', 'language']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_templates');
    }
};
