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
            $table->json('business_hours')->nullable()->after('show_on_desktop');
            $table->text('page_targeting')->nullable()->after('business_hours');
            $table->integer('time_on_page')->default(0)->after('page_targeting');
            $table->boolean('exit_intent')->default(false)->after('time_on_page');
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
