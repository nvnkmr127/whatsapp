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
        Schema::create('onboarding_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('account_created')->default(true);
            $table->boolean('whatsapp_connected')->default(false);
            $table->boolean('business_profile_completed')->default(false);
            $table->boolean('first_template_created')->default(false);
            $table->boolean('first_campaign_created')->default(false);
            $table->boolean('ai_training_completed')->default(false);
            $table->boolean('onboarding_completed')->default(false);
            $table->timestamp('last_activity_at')->useCurrent();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('onboarding_statuses');
    }
};
