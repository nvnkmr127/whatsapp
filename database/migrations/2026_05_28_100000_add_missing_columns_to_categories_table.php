<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            if (! Schema::hasColumn('categories', 'team_id')) {
                $table->foreignId('team_id')->after('id')->constrained()->cascadeOnDelete();
            }
            if (! Schema::hasColumn('categories', 'name')) {
                $table->string('name')->after('team_id');
            }
            if (! Schema::hasColumn('categories', 'description')) {
                $table->text('description')->nullable()->after('name');
            }
            if (! Schema::hasColumn('categories', 'color')) {
                $table->string('color', 7)->default('#3B82F6')->after('description');
            }
            if (! Schema::hasColumn('categories', 'icon')) {
                $table->string('icon', 10)->nullable()->after('color');
            }
            if (! Schema::hasColumn('categories', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('icon');
            }
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('team_id');
            $table->dropColumn(['name', 'description', 'color', 'icon', 'is_active']);
        });
    }
};
