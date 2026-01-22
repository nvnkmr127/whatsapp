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
        Schema::table('contacts', function (Blueprint $table) {
            // Counter fields
            $table->unsignedInteger('message_count')->default(0)->after('phone_number');
            $table->unsignedInteger('inbound_message_count')->default(0)->after('message_count');
            $table->unsignedInteger('outbound_message_count')->default(0)->after('inbound_message_count');
            $table->unsignedInteger('conversation_count')->default(0)->after('outbound_message_count');

            // Engagement metrics
            $table->unsignedTinyInteger('engagement_score')->default(0)->after('conversation_count');
            $table->string('lifecycle_state')->default('new')->after('engagement_score'); // new, active, engaged, dormant, churned

            // Response time (in seconds)
            $table->unsignedInteger('avg_response_time')->nullable()->after('lifecycle_state');

            // Derived timestamps
            $table->timestamp('last_agent_message_at')->nullable()->after('last_customer_message_at');
            $table->unsignedInteger('days_since_last_message')->nullable()->after('last_interaction_at');

            // Flags
            $table->boolean('is_within_24h_window')->default(false)->after('days_since_last_message');

            // Campaign tracking
            $table->foreignId('last_campaign_id')->nullable()->constrained('campaigns')->nullOnDelete()->after('is_within_24h_window');

            // Tag count
            $table->unsignedInteger('total_tags_count')->default(0)->after('last_campaign_id');

            // Consent metrics
            $table->unsignedInteger('consent_age_days')->nullable()->after('opt_in_expires_at');
            $table->boolean('is_consent_expired')->default(false)->after('consent_age_days');

            // Version for optimistic locking
            $table->unsignedBigInteger('version')->default(0)->after('updated_at');

            // Indexes for derived fields
            $table->index('engagement_score', 'idx_contacts_engagement');
            $table->index('lifecycle_state', 'idx_contacts_lifecycle');
            $table->index('is_within_24h_window', 'idx_contacts_24h_window');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropIndex('idx_contacts_engagement');
            $table->dropIndex('idx_contacts_lifecycle');
            $table->dropIndex('idx_contacts_24h_window');

            $table->dropForeign(['last_campaign_id']);

            $table->dropColumn([
                'message_count',
                'inbound_message_count',
                'outbound_message_count',
                'conversation_count',
                'engagement_score',
                'lifecycle_state',
                'avg_response_time',
                'last_agent_message_at',
                'days_since_last_message',
                'is_within_24h_window',
                'last_campaign_id',
                'total_tags_count',
                'consent_age_days',
                'is_consent_expired',
                'version',
            ]);
        });
    }
};
