<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->integer('team_limit')->default(1)->after('agent_limit');
        });

        // Set defaults for existing plans
        DB::table('plans')->where('name', 'basic')->update(['team_limit' => 1]);
        DB::table('plans')->where('name', 'pro')->update(['team_limit' => 5]);
        DB::table('plans')->where('name', 'enterprise')->update(['team_limit' => 0]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plans', function (Blueprint $table) {
            $table->dropColumn('team_limit');
        });
    }
};
