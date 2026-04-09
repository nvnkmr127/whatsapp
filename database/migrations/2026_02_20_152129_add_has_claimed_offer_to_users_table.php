<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds `has_claimed_offer` to the users table.
 *
 * This is the **user-level** offer claim flag (lives on the person, not
 * the team). It is stamped TRUE whenever any team owned by this user
 * successfully claims the launch offer, and is NEVER cleared — not when
 * the email changes, not when the team is deleted, not when WABA is
 * disconnected.
 *
 * The OfferEligibilityService reads this flag as an additional rule so
 * re-registration under a new email or after team deletion still respects
 * the original claim.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('has_claimed_offer')->default(false)->after('unsubscribe_token');
            $table->timestamp('offer_claimed_at')->nullable()->after('has_claimed_offer');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['has_claimed_offer', 'offer_claimed_at']);
        });
    }
};
