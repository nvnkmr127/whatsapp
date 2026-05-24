<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (\Illuminate\Support\Facades\DB::getDriverName() === 'sqlite') {
            return;
        }

        // MySQL requires a full column redefinition to change type.
        // The allowed values match the three states checked throughout the codebase.
        \Illuminate\Support\Facades\DB::statement(
            "ALTER TABLE conversations
             MODIFY COLUMN sla_status ENUM('on_track','breaching_soon','breached') NULL DEFAULT NULL"
        );
    }

    public function down(): void
    {
        if (\Illuminate\Support\Facades\DB::getDriverName() === 'sqlite') {
            return;
        }

        \Illuminate\Support\Facades\DB::statement(
            "ALTER TABLE conversations
             MODIFY COLUMN sla_status VARCHAR(255) NULL DEFAULT NULL"
        );
    }
};
