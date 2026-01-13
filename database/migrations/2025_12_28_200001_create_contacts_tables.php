<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('phone_number'); // Format: E.164
            $table->string('email')->nullable();
            $table->json('custom_attributes')->nullable(); // For flexible CRM data
            $table->string('crm_source_id')->nullable(); // For ERP integration
            $table->timestamps();

            $table->unique(['team_id', 'phone_number']);
        });

        Schema::create('contact_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('color')->default('#cbd5e1');
            $table->timestamps();
        });

        Schema::create('contact_tag_pivot', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('contact_tags')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_tag_pivot');
        Schema::dropIfExists('contact_tags');
        Schema::dropIfExists('contacts');
    }
};
