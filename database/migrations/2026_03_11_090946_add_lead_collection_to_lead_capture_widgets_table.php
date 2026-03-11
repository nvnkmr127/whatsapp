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
        Schema::table('lead_capture_widgets', function (Blueprint $table) {
            $table->boolean('collect_name')->default(false)->after('widget_color');
            $table->boolean('collect_email')->default(false)->after('collect_name');
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
