<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'locked_fields')) {
                $table->json('locked_fields')->nullable()->after('availability')->comment('Fields owned by WhatsApp (not overwritten by sync)');
            }
            if (!Schema::hasColumn('products', 'last_external_update_at')) {
                $table->timestamp('last_external_update_at')->nullable()->after('updated_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['locked_fields', 'last_external_update_at']);
        });
    }
};
