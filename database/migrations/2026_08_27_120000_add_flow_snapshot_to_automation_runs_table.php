<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('automation_runs', function (Blueprint $table) {
            // Snapshot of the automation's flow_data taken when the run started, so edits/
            // republishes to the automation don't corrupt runs that are already in flight.
            $table->json('flow_snapshot')->nullable()->after('state_data');
        });
    }

    public function down(): void
    {
        Schema::table('automation_runs', function (Blueprint $table) {
            $table->dropColumn('flow_snapshot');
        });
    }
};
