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
        Schema::table('categories', function (Blueprint $table) {
            if (!Schema::hasColumn('categories', 'team_id')) {
                $table->foreignId('team_id')->after('id')->constrained()->onDelete('cascade');
            }
            if (!Schema::hasColumn('categories', 'name')) {
                $table->string('name')->after('team_id');
            }
            if (!Schema::hasColumn('categories', 'description')) {
                $table->text('description')->nullable()->after('name');
            }
            if (!Schema::hasColumn('categories', 'color')) {
                $table->string('color', 7)->default('#3B82F6')->after('description');
            }
            if (!Schema::hasColumn('categories', 'icon')) {
                $table->string('icon', 10)->nullable()->after('color');
            }
            if (!Schema::hasColumn('categories', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('icon');
            }
        });

        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'category_id')) {
                $table->foreignId('category_id')->nullable()->after('team_id')->constrained()->nullOnDelete();
            }
        });

        Schema::table('contacts', function (Blueprint $table) {
            if (!Schema::hasColumn('contacts', 'category_id')) {
                $table->foreignId('category_id')->nullable()->after('team_id')->constrained()->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropColumn('category_id');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn(['team_id', 'name', 'description', 'color', 'icon', 'is_active']);
        });
    }
};
