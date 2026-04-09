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
            $table->unsignedInteger('click_count')->default(0)->after('scan_count');
            $table->string('qr_color')->default('#000000')->after('placeholder_email');
            $table->string('qr_bg_color')->default('#ffffff')->after('qr_color');
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
