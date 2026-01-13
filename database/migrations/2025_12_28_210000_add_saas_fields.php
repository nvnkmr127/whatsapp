<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // 1. Subscription Tracking on Teams (Tenants)
        Schema::table('teams', function (Blueprint $table) {
            $table->string('subscription_plan')->nullable()->after('personal_team'); // 'basic', 'pro', 'enterprise'
            $table->string('subscription_status')->default('trial')->after('subscription_plan'); // 'active', 'trial', 'canceled'
            $table->timestamp('trial_ends_at')->nullable()->after('subscription_status');
            $table->timestamp('subscription_ends_at')->nullable()->after('trial_ends_at');
        });

        // 2. Super Admin Flag on Users
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_super_admin')->default(false)->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropColumn(['subscription_plan', 'subscription_status', 'trial_ends_at', 'subscription_ends_at']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_super_admin']);
        });
    }
};
