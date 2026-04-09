<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Change the default value of opt_in_status to 'opted_in'
        Schema::table('contacts', function (Blueprint $table) {
            $table->enum('opt_in_status', ['none', 'opted_in', 'opted_out'])->default('opted_in')->change();
        });

        // 2. Set all existing contacts with 'none' status to 'opted_in'
        // This ensures the current blocked messages will be allowed
        DB::table('contacts')
            ->where('opt_in_status', 'none')
            ->orWhereNull('opt_in_status')
            ->update([
                'opt_in_status' => 'opted_in',
                'opt_in_at' => now(),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert default to 'none'
        Schema::table('contacts', function (Blueprint $table) {
            $table->enum('opt_in_status', ['none', 'opted_in', 'opted_out'])->default('none')->change();
        });

        // We don't necessarily want to revert existing contacts, as it's destructive.
    }
};
