<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('type')->default('custom'); // organic, paid, referral, social, custom
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['team_id', 'name']);
        });

        Schema::table('contacts', function (Blueprint $table) {
            $table->foreignId('lead_source_id')->nullable()->constrained('lead_sources')->nullOnDelete();
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('utm_term')->nullable();
            $table->string('utm_content')->nullable();
            $table->string('referrer_url')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropForeign(['lead_source_id']);
            $table->dropColumn([
                'lead_source_id',
                'utm_source',
                'utm_medium',
                'utm_campaign',
                'utm_term',
                'utm_content',
                'referrer_url',
            ]);
        });

        Schema::dropIfExists('lead_sources');
    }
};
