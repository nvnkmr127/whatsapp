<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private function indexExists($table, $indexName): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list('{$table}')");
            foreach ($indexes as $index) {
                if ($index->name === $indexName) {
                    return true;
                }
            }
            return false;
        }

        $indexes = DB::select("SHOW INDEX FROM {$table} WHERE Key_name = ?", [$indexName]);
        return !empty($indexes);
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            if (!$this->indexExists('activity_logs', 'activity_logs_team_id_created_at_index')) {
                $table->index(['team_id', 'created_at']);
            }
        });

        Schema::table('products', function (Blueprint $table) {
            if (!$this->indexExists('products', 'products_team_id_category_id_index')) {
                $table->index(['team_id', 'category_id']);
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            if (!$this->indexExists('orders', 'orders_team_id_status_index')) {
                $table->index(['team_id', 'status']);
            }
            if (!$this->indexExists('orders', 'orders_contact_id_index')) {
                $table->index(['contact_id']);
            }
        });

        Schema::table('automations', function (Blueprint $table) {
            if (!$this->indexExists('automations', 'automations_team_id_is_active_index')) {
                $table->index(['team_id', 'is_active']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('activity_logs', function (Blueprint $table) {
            if ($this->indexExists('activity_logs', 'activity_logs_team_id_created_at_index')) {
                $table->dropIndex(['team_id', 'created_at']);
            }
        });

        Schema::table('products', function (Blueprint $table) {
            if ($this->indexExists('products', 'products_team_id_category_id_index')) {
                $table->dropIndex(['team_id', 'category_id']);
            }
        });

        Schema::table('orders', function (Blueprint $table) {
            if ($this->indexExists('orders', 'orders_team_id_status_index')) {
                $table->dropIndex(['team_id', 'status']);
            }
            if ($this->indexExists('orders', 'orders_contact_id_index')) {
                $table->dropIndex(['contact_id']);
            }
        });

        Schema::table('automations', function (Blueprint $table) {
            if ($this->indexExists('automations', 'automations_team_id_is_active_index')) {
                $table->dropIndex(['team_id', 'is_active']);
            }
        });
    }
};
