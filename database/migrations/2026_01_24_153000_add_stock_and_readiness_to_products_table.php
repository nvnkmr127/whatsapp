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
        Schema::table('products', function (Blueprint $table) {
            $table->integer('stock_quantity')->default(0)->after('availability');
            $table->boolean('manage_stock')->default(false)->after('stock_quantity');
            $table->string('sync_state')->default('local')->after('meta_product_id'); // local, syncing, synced, failed
            $table->text('sync_errors')->nullable()->after('sync_state');
            $table->boolean('is_active')->default(true)->after('sync_errors');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['stock_quantity', 'manage_stock', 'sync_state', 'sync_errors', 'is_active']);
        });
    }
};
