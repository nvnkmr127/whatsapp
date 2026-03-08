<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('domain')->nullable();
            $table->string('industry')->nullable();
            $table->string('size')->nullable(); // 1-10, 11-50, etc.
            $table->string('website')->nullable();
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->nullable();
            $table->string('postal_code')->nullable();
            $table->text('description')->nullable();
            $table->string('logo_url')->nullable();
            $table->json('custom_fields')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['team_id', 'name']);
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->constrained('companies')->nullOnDelete();
            $table->string('job_title')->nullable();
        });

        Schema::create('contact_relationships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->foreignId('related_contact_id')->constrained('contacts')->cascadeOnDelete();
            $table->string('type'); // colleague, referral, family, etc.
            $table->timestamps();
            
            $table->unique(['contact_id', 'related_contact_id', 'type'], 'unique_relationship');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_relationships');
        
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropForeign(['company_id']);
            $table->dropColumn(['company_id', 'job_title']);
        });

        Schema::dropIfExists('companies');
    }
};
