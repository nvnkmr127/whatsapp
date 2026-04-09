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
        Schema::table('lead_capture_widgets', function (Blueprint $table) {
            $table->string('footer_text')->nullable()->after('welcome_message');
            $table->string('placeholder_name')->nullable()->after('footer_text');
            $table->string('placeholder_email')->nullable()->after('placeholder_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('lead_capture_widgets', function (Blueprint $table) {
            //
        });
    }
};
