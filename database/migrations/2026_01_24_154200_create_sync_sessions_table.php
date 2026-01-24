<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sync_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('integration_id')->constrained()->cascadeOnDelete();
            $table->string('type'); // products, orders, etc
            $table->string('status')->default('running'); // running, completed, partially_failed, failed
            $table->integer('total_entities')->default(0);
            $table->integer('processed_entities')->default(0);
            $table->integer('failed_entities')->default(0);
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->json('error_summary')->nullable();
            $table->json('metadata')->nullable(); // stores 'since_id' or 'updated_at_min' used
            $table->timestamps();
        });

        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'sync_attempts')) {
                $table->integer('sync_attempts')->default(0)->after('sync_state');
            }
            if (!Schema::hasColumn('products', 'last_sync_error')) {
                $table->text('last_sync_error')->nullable()->after('sync_attempts');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_sessions');
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['sync_attempts', 'last_sync_error']);
        });
    }
};
