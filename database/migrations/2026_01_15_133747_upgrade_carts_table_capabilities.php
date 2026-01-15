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
        Schema::table('carts', function (Blueprint $table) {
            // Core columns (Ensure they exist)
            if (!Schema::hasColumn('carts', 'uuid')) {
                $table->string('uuid')->unique()->after('id');
            }
            if (!Schema::hasColumn('carts', 'team_id')) {
                $table->foreignId('team_id')->after('uuid')->nullable()->index();
            }
            if (!Schema::hasColumn('carts', 'contact_id')) {
                $table->foreignId('contact_id')->constrained()->cascadeOnDelete()->after('team_id');
            }
            if (!Schema::hasColumn('carts', 'items')) {
                $table->json('items')->nullable()->after('contact_id');
            }
            if (!Schema::hasColumn('carts', 'total_amount')) {
                $table->decimal('total_amount', 10, 2)->default(0)->after('items');
            }
            if (!Schema::hasColumn('carts', 'currency')) {
                $table->string('currency')->default('USD')->after('total_amount');
            }
            if (!Schema::hasColumn('carts', 'status')) {
                $table->string('status')->default('active')->after('currency');
            }
            if (!Schema::hasColumn('carts', 'context_key')) {
                $table->string('context_key')->nullable()->after('status')->index();
            }
            if (!Schema::hasColumn('carts', 'expires_at')) {
                $table->timestamp('expires_at')->nullable()->after('context_key');
            }
            // New columns
            if (!Schema::hasColumn('carts', 'reminder_sent_at')) {
                $table->timestamp('reminder_sent_at')->nullable()->after('expires_at');
            }
            if (!Schema::hasColumn('carts', 'meta_data')) {
                $table->json('meta_data')->nullable()->after('reminder_sent_at');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('carts', function (Blueprint $table) {
            // We can drop columns here if needed, but safety first in dev
        });
    }
};
