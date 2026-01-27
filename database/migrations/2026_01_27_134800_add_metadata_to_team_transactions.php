<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('team_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('team_transactions', 'metadata')) {
                $table->json('metadata')->nullable()->after('invoice_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('team_transactions', function (Blueprint $table) {
            $table->dropColumn('metadata');
        });
    }
};
