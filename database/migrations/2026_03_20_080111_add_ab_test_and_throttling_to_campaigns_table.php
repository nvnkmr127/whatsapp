<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->boolean('is_ab_test')->default(false)->after('template_name');
            $table->string('template_b_id')->nullable()->after('is_ab_test');
            $table->string('template_b_name')->nullable()->after('template_b_id');
            $table->integer('split_ratio')->default(50)->after('template_b_name'); // Percentage for A
            $table->integer('send_rate')->nullable()->after('split_ratio'); // msgs / hour
            $table->timestamp('error_notified_at')->nullable()->after('error_message');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropColumn(['is_ab_test', 'template_b_id', 'template_b_name', 'split_ratio', 'send_rate', 'error_notified_at']);
        });
    }
};
