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
        Schema::create('webhook_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // e.g., "Shopify Store", "Stripe Payments"
            $table->string('slug')->unique(); // e.g., "shopify-store-1"
            $table->string('platform')->default('custom'); // shopify, stripe, woocommerce, custom, etc.
            $table->string('auth_method')->default('none'); // hmac, basic, api_key, none
            $table->text('auth_config')->nullable(); // JSON config for auth
            $table->json('field_mappings')->nullable(); // Field mapping rules
            $table->json('transformation_rules')->nullable(); // Data transformation rules
            $table->json('action_config')->nullable(); // What to do with mapped data
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('total_received')->default(0);
            $table->unsignedInteger('total_processed')->default(0);
            $table->unsignedInteger('total_failed')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('webhook_sources');
    }
};
