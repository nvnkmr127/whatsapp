<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('message_bots', function (Blueprint $table) {
            if (!Schema::hasColumn('message_bots', 'team_id')) {
                $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete()->after('id');
                $table->index('team_id');
            }
        });

        Schema::table('template_bots', function (Blueprint $table) {
            if (!Schema::hasColumn('template_bots', 'team_id')) {
                $table->foreignId('team_id')->nullable()->constrained()->nullOnDelete()->after('id');
                $table->index('team_id');
            }
            if (!Schema::hasColumn('template_bots', 'whatsapp_template_id')) {
                $table->string('whatsapp_template_id')->nullable()->after('template_id');
                $table->index('whatsapp_template_id');
            }
        });

        if (Schema::hasColumn('template_bots', 'template_id') && Schema::hasColumn('template_bots', 'whatsapp_template_id')) {
            DB::table('template_bots')
                ->whereNull('whatsapp_template_id')
                ->update(['whatsapp_template_id' => DB::raw('template_id')]);
        }
    }

    public function down(): void
    {
        Schema::table('template_bots', function (Blueprint $table) {
            if (Schema::hasColumn('template_bots', 'whatsapp_template_id')) {
                $table->dropIndex(['whatsapp_template_id']);
                $table->dropColumn('whatsapp_template_id');
            }
            if (Schema::hasColumn('template_bots', 'team_id')) {
                $table->dropConstrainedForeignId('team_id');
            }
        });

        Schema::table('message_bots', function (Blueprint $table) {
            if (Schema::hasColumn('message_bots', 'team_id')) {
                $table->dropConstrainedForeignId('team_id');
            }
        });
    }
};
