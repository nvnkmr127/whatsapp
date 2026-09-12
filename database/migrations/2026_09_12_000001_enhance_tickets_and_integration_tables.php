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
        Schema::table('tickets', function (Blueprint $table) {
            if (! Schema::hasColumn('tickets', 'ticket_number')) {
                $table->string('ticket_number')->nullable()->unique()->after('id');
            }
            if (! Schema::hasColumn('tickets', 'external_id')) {
                $table->string('external_id')->nullable()->index()->after('ticket_number');
            }
            if (! Schema::hasColumn('tickets', 'category')) {
                $table->string('category')->nullable()->index()->after('subject');
            }
            if (! Schema::hasColumn('tickets', 'assigned_to')) {
                $table->foreignId('assigned_to')->nullable()->after('priority')->constrained('users')->nullOnDelete();
            }
            if (! Schema::hasColumn('tickets', 'source')) {
                $table->string('source')->default('bot_automation')->after('assigned_to');
            }
            if (! Schema::hasColumn('tickets', 'custom_fields')) {
                $table->json('custom_fields')->nullable()->after('description');
            }
            if (! Schema::hasColumn('tickets', 'resolved_at')) {
                $table->timestamp('resolved_at')->nullable()->after('custom_fields');
            }
            if (! Schema::hasColumn('tickets', 'resolution_notes')) {
                $table->text('resolution_notes')->nullable()->after('resolved_at');
            }
            if (! Schema::hasColumn('tickets', 'resolution_image_url')) {
                $table->string('resolution_image_url')->nullable()->after('resolution_notes');
            }
            if (! Schema::hasColumn('tickets', 'metadata')) {
                $table->json('metadata')->nullable()->after('resolution_image_url');
            }
        });

        Schema::table('teams', function (Blueprint $table) {
            if (! Schema::hasColumn('teams', 'ticket_settings')) {
                $table->json('ticket_settings')->nullable()->after('updated_at');
            }
        });

        Schema::table('whatsapp_flow_responses', function (Blueprint $table) {
            if (! Schema::hasColumn('whatsapp_flow_responses', 'whatsapp_flow_version_id')) {
                $table->unsignedBigInteger('whatsapp_flow_version_id')->nullable()->after('whatsapp_flow_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn([
                'ticket_number',
                'external_id',
                'category',
                'assigned_to',
                'source',
                'custom_fields',
                'resolved_at',
                'resolution_notes',
                'resolution_image_url',
                'metadata',
            ]);
        });

        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn('ticket_settings');
        });

        Schema::table('whatsapp_flow_responses', function (Blueprint $table) {
            $table->dropColumn('whatsapp_flow_version_id');
        });
    }
};
