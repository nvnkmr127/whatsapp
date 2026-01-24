<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('whatsapp_flow_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('whatsapp_flow_id')->constrained()->cascadeOnDelete();
            $table->string('version_hash')->nullable(); // Unique hash for content
            $table->integer('version_number')->default(1);
            $table->string('status')->default('DRAFT'); // DRAFT, PUBLISHED, ARCHIVED
            $table->string('meta_publish_id')->nullable(); // ID returned by Meta on publish

            // Content Snapshots
            $table->json('design_data')->nullable(); // Internal Builder Data
            $table->json('flow_json')->nullable();   // Meta JSON
            $table->json('entry_point_config')->nullable(); // Entry Point Permissions

            $table->timestamps();

            // Indexes
            $table->index(['whatsapp_flow_id', 'version_number']);
        });

        // Add version tracking to main table
        Schema::table('whatsapp_flows', function (Blueprint $table) {
            $table->foreignId('active_version_id')->nullable()->constrained('whatsapp_flow_versions')->nullOnDelete();
            $table->integer('latest_version_number')->default(0);
        });
    }

    public function down()
    {
        Schema::table('whatsapp_flows', function (Blueprint $table) {
            $table->dropForeign(['active_version_id']);
            $table->dropColumn(['active_version_id', 'latest_version_number']);
        });
        Schema::dropIfExists('whatsapp_flow_versions');
    }
};
