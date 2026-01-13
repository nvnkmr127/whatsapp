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
        Schema::table('messages', function (Blueprint $table) {
            $table->string('media_url')->nullable()->after('content'); // Local / stored URL
            $table->string('original_media_url')->nullable()->after('media_url'); // Meta URL
            $table->string('media_type')->nullable()->after('original_media_url'); // Mime type
            $table->string('caption')->nullable()->after('media_type');
            $table->string('media_id')->nullable()->after('caption'); // Meta Media ID
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn(['media_url', 'original_media_url', 'media_type', 'caption', 'media_id']);
        });
    }
};
