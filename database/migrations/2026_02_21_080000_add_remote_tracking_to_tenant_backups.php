<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * CVE-3: Restore Without Ownership/Integrity Check.
     * Adding remote tracking fields to ensure we can:
     * 1. Bind a backup to the specific Google Drive account that created it.
     * 2. Use direct file IDs instead of name-based lookups (faster, safer).
     * 3. Verify object-level ownership before restoration.
     */
    public function up(): void
    {
        Schema::table('tenant_backups', function (Blueprint $table) {
            $table->string('remote_account_id')->nullable()->after('disk')
                ->comment('Unique ID of the cloud account (e.g. Google User sub)');
            $table->string('remote_file_id')->nullable()->after('remote_account_id')
                ->comment('Unique ID of the file in the remote system (e.g. Google Drive File ID)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenant_backups', function (Blueprint $table) {
            $table->dropColumn(['remote_account_id', 'remote_file_id']);
        });
    }
};
